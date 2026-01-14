<?php

declare(strict_types=1);

namespace izi\prestashop\MerchantApi\Handler\Basket;

use izi\prestashop\Cart\Util\ProductHelper;
use izi\prestashop\Common\Basket\Notice;
use izi\prestashop\Entities\BasketInterface;
use izi\prestashop\MerchantApi\Exception\MalformedRequestException;
use izi\prestashop\MerchantApi\Model\Basket\Request\BasketEvent;
use izi\prestashop\MerchantApi\Model\Basket\Request\EventType;
use izi\prestashop\ObjectModel\ObjectManagerInterface;
use izi\prestashop\ObjectModel\Repository\ProductRepository;
use izi\prestashop\Product\ReferenceId;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class ProductsQuantityEventHandler implements BasketEventHandlerInterface
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var ObjectManagerInterface
     */
    private $manager;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(TranslatorInterface $translator, ObjectManagerInterface $manager, LoggerInterface $logger)
    {
        $this->translator = $translator;
        $this->manager = $manager;
        $this->logger = $logger;
    }

    public static function getHandledEventType(): string
    {
        return EventType::ProductsQuantity()->value;
    }

    public function handle(BasketInterface $basket, BasketEvent $event): ?Notice
    {
        if (EventType::ProductsQuantity() !== $type = $event->getType()) {
            throw new \DomainException(\sprintf('Unsupported event type "%s".', $type->value));
        }

        $cart = $basket->getEntity();

        if (!$cart instanceof \Cart) {
            throw new \InvalidArgumentException(\sprintf('Expected basket entity to be an instance of "%s", "%s" given.', \Cart::class, \get_class($cart)));
        }

        foreach ($event->getQuantityEventData() as $data) {
            if (null === $referenceId = ReferenceId::fromString($data->getProductId())) {
                throw MalformedRequestException::create();
            }

            $productId = $referenceId->getProductId();
            $combinationId = (int) $referenceId->getCombinationId();
            $customizationId = (int) $referenceId->getCustomizationId();
            $quantity = (int) $data->getQuantity()->getQuantity();

            if (null !== $error = $this->updateCartQuantity($cart, $productId, $combinationId, $customizationId, $quantity)) {
                return Notice::error($error);
            }
        }

        return null;
    }

    /* @TODO: refactor to use {@see IncrementCartQuantityHandler} */
    private function updateCartQuantity(\Cart $cart, int $productId, int $combinationId, int $customizationId, int $quantity): ?string
    {
        $currentQuantity = ProductHelper::getCartQuantity($cart, $productId, $combinationId, $customizationId);

        if (0 === $currentQuantity) {
            return 0 >= $quantity ? null : $this->translator->trans('This product is no longer in your cart.', [], 'Modules.Inpostizi.Errors');
        }

        if (0 >= $quantity) {
            return $this->deleteProduct($cart, $productId, $combinationId, $customizationId);
        }

        if (0 === $deltaQuantity = $quantity - $currentQuantity) {
            return null;
        }

        if (null !== $error = $this->checkMinimalQuantity($productId, $combinationId, $quantity, (int) $cart->id_lang)) {
            return $error;
        }

        $availableQuantity = $this->getAvailableQuantity($cart, $productId, $combinationId, $customizationId);
        if (null !== $availableQuantity && $deltaQuantity > $availableQuantity) {
            return $this->translator->trans('The available purchase order quantity for this product is %quantity%.', [
                '%quantity%' => $availableQuantity + $currentQuantity,
            ], 'Shop.Notifications.Error');
        }

        try {
            $result = $cart->updateQty(
                abs($deltaQuantity),
                $productId,
                $combinationId,
                $customizationId,
                $deltaQuantity > 0 ? 'up' : 'down',
                0,
                null,
                false
            );
        } catch (\Exception $e) {
            $result = false;
        }

        if (false !== $result) {
            return null;
        }

        $this->logger->critical('Could not update product [{productId}] quantity in cart #{cartId}.', [
            'productId' => implode('-', [$productId, $combinationId, $customizationId]),
            'cartId' => $cart->id,
            'exception' => $e ?? null,
        ]);

        return $this->translator->trans('Could not update product quantity.', [], 'Modules.Inpostizi.Errors');
    }

    private function deleteProduct(\Cart $cart, int $productId, int $combinationId, int $customizationId): ?string
    {
        try {
            $result = $cart->deleteProduct($productId, $combinationId, $customizationId);
        } catch (\Exception $e) {
            $result = false;
        }

        if (false !== $result) {
            return null;
        }

        $this->logger->critical('Could not delete product [{productId}] from cart #{cartId}.', [
            'productId' => implode('-', [$productId, $combinationId, $customizationId]),
            'cartId' => $cart->id,
            'exception' => $e ?? null,
        ]);

        return $this->translator->trans('Could not delete the product from your cart.', [], 'Modules.Inpostizi.Errors');
    }

    private function checkMinimalQuantity(int $productId, int $combinationId, int $quantity, int $languageId): ?string
    {
        $product = $this->manager->getRepository(\Product::class)->find($productId, $languageId);

        if (null === $product) {
            throw new \RuntimeException('Product does not exist');
        }

        $combination = 0 !== $combinationId
            ? $this->manager->getRepository(\Combination::class)->find($combinationId)
            : null;

        $minimalQuantity = null === $combination
            ? $product->minimal_quantity
            : $combination->minimal_quantity;

        if ($quantity >= $minimalQuantity) {
            return null;
        }

        return $this->translator->trans('The minimum purchase order quantity for the product %product% is %quantity%.', [
            '%product%' => $product->name,
            '%quantity%' => $minimalQuantity,
        ], 'Shop.Notifications.Error');
    }

    /**
     * @return int|null quantity or null if product is available out of stock
     */
    private function getAvailableQuantity(\Cart $cart, int $productId, int $combinationId, int $customizationId): ?int
    {
        /** @var ProductRepository $repository */
        $repository = $this->manager->getRepository(\Product::class);

        if ($repository->isAvailableOutOfStock($productId)) {
            return null;
        }

        return $repository->getAvailableQuantity($productId, $combinationId, $cart, $customizationId);
    }
}
