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
use izi\prestashop\Product\ReferenceId;
use Symfony\Contracts\Translation\TranslatorInterface;

final class RelatedProductsEventHandler implements BasketEventHandlerInterface
{
    /**
     * @var CommandBusInterface
     */
    private $bus;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(CommandBusInterface $bus, TranslatorInterface $translator)
    {
        $this->bus = $bus;
        $this->translator = $translator;
    }

    public static function getHandledEventType(): string
    {
        return EventType::RelatedProducts()->value;
    }

    public function handle(BasketInterface $basket, BasketEvent $event): ?Notice
    {
        if (EventType::RelatedProducts() !== $type = $event->getType()) {
            throw new \DomainException(\sprintf('Unsupported event type "%s".', $type->value));
        }

        $cart = $basket->getEntity();

        if (!$cart instanceof \Cart) {
            throw new \InvalidArgumentException(\sprintf('Expected basket entity to be an instance of "%s", "%s" given.', \Cart::class, \get_class($cart)));
        }

        foreach ($event->getRelatedProductsEventData() as $relatedProduct) {
            try {
                $this->addRelatedProduct($cart, $relatedProduct);
            } catch (ProductAlreadyInCartException $e) {
                // ignore silently: the product had already been added to the cart
            } catch (ProductNotFoundException $e) {
                return Notice::error($this->translator->trans('Product not found', [], 'Shop.Notifications.Error'));
            } catch (ProductOutOfStockException $e) {
                return Notice::error($this->translator->trans('The available purchase order quantity for this product is %quantity%.', [
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
}
