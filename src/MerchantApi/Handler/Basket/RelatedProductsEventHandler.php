<?php

declare(strict_types=1);

namespace izi\prestashop\MerchantApi\Handler\Basket;

use izi\prestashop\Cart\Exception\ProductAlreadyInCartException;
use izi\prestashop\CommandBusInterface;
use izi\prestashop\Common\Basket\Notice;
use izi\prestashop\Entities\BasketInterface;
use izi\prestashop\MerchantApi\Command\Basket\AddProductToCartCommand;
use izi\prestashop\MerchantApi\Exception\CannotAddProductException;
use izi\prestashop\MerchantApi\Exception\MalformedRequestException;
use izi\prestashop\MerchantApi\Exception\ProductNotFoundException;
use izi\prestashop\MerchantApi\Exception\ProductOutOfStockException;
use izi\prestashop\MerchantApi\Model\Basket\Request\BasketEvent;
use izi\prestashop\MerchantApi\Model\Basket\Request\EventType;
use izi\prestashop\MerchantApi\Model\Basket\Request\RelatedProductData;
use izi\prestashop\ObjectModel\ObjectManagerInterface;
use izi\prestashop\Product\ReferenceId;
use izi\prestashop\Translation\LegacyTranslator;
use Psr\Log\LoggerInterface;

final class RelatedProductsEventHandler implements BasketEventHandlerInterface
{
    /**
     * @internal
     *
     * @deprecated
     */
    public const TRANSLATION_SOURCE = 'relatedproductseventhandler';

    /**
     * @var CommandBusInterface
     */
    private $bus;

    /**
     * @var \Context
     */
    private $context;

    /**
     * @param CommandBusInterface $bus
     * @param \Context $context
     */
    public function __construct($bus, \Context $context/*, ObjectManagerInterface $manager, LoggerInterface $logger*/)
    {
        $this->context = $context;

        if ($bus instanceof \Module) {
            $args = func_get_args();
            $bus = $this->createCommandBus($context, $bus, $args[2], $args[3]);
        }

        if (!$bus instanceof CommandBusInterface) {
            throw new \InvalidArgumentException(sprintf('Expected parameter $bus to be an instance of "%s", "%s" given.', CommandBusInterface::class, get_debug_type($bus)));
        }

        $this->bus = $bus;
    }

    public static function getHandledEventType(): string
    {
        return EventType::RelatedProducts()->value;
    }

    public function handle(BasketInterface $basket, BasketEvent $event): ?Notice
    {
        if (EventType::RelatedProducts() !== $type = $event->getType()) {
            throw new \DomainException(sprintf('Unsupported event type "%s".', $type->value));
        }

        $cart = $basket->getEntity();

        if (!$cart instanceof \Cart) {
            throw new \InvalidArgumentException(sprintf('Expected basket entity to be an instance of "%s", "%s" given.', \Cart::class, get_class($cart)));
        }

        foreach ($event->getRelatedProductsEventData() as $relatedProduct) {
            try {
                $this->addRelatedProduct($cart, $relatedProduct);
            } catch (ProductAlreadyInCartException $e) {
                // ignore silently
            } catch (ProductNotFoundException $e) {
                return Notice::error($this->context->getTranslator()->trans('Product not found', [], 'Shop.Notifications.Error'));
            } catch (ProductOutOfStockException $e) {
                return Notice::error($this->context->getTranslator()->trans('The available purchase order quantity for this product is %quantity%.', [
                    '%quantity%' => $e->getAvailableQuantity(),
                ], 'Shop.Notifications.Error'));
            } catch (CannotAddProductException $e) {
                return Notice::error($e->getMessage());
            }
        }

        return null;
    }

    private function addRelatedProduct(\Cart $cart, RelatedProductData $relatedProduct): void
    {
        $referenceId = ReferenceId::fromString($relatedProduct->getProductId());

        if (null === $referenceId) {
            throw ProductNotFoundException::create();
        }

        if ($referenceId->hasCustomization()) {
            throw new MalformedRequestException('Adding customizable products is not supported for related products.');
        }

        $productId = $referenceId->getProductId();
        $combinationId = $referenceId->getCombinationId();
        $quantity = (int) $relatedProduct->getQuantity()->getQuantity();

        $this->bus->handle(new AddProductToCartCommand($cart, $productId, $combinationId, $quantity));
    }

    private function createCommandBus(\Context $context, \Module $module, ObjectManagerInterface $manager, LoggerInterface $logger): CommandBusInterface
    {
        @trigger_error(sprintf('Passing $module, $manager, and $logger as arguments for "%s::__construct()" is deprecated.', __CLASS__), E_USER_DEPRECATED);

        $handler = new AddProductToCartHandler(
            $context,
            $manager->getRepository(\Product::class),
            new LegacyTranslator($module->name),
            $logger
        );

        return new class($handler) implements CommandBusInterface {
            /**
             * @var callable
             */
            private $handler;

            public function __construct(callable $handler)
            {
                $this->handler = $handler;
            }

            public function handle($command)
            {
                return ($this->handler)($command);
            }
        };
    }
}
