<?php

namespace izi\prestashop\Handler;

use izi\item\BasketNotice;
use izi\prestashop\Logger;

final class ProductsQuantityEventHandler implements BasketEventHandlerInterface
{
    public const EVENT_TYPE = 'PRODUCTS_QUANTITY';
    private const TRANSLATION_SOURCE = 'productsquantityeventhandler';

    private $context;
    private $translator;
    private $module;

    public function __construct(\Context $context, \Module $module = null)
    {
        $this->context = $context;
        $this->translator = $context->getTranslator();
        $this->module = $module ?? \Module::getInstanceByName('inpostizi');
    }

    public function handle(\Cart $cart, $event): ?BasketNotice
    {
        if (self::EVENT_TYPE !== $event->event_type) {
            throw new \InvalidArgumentException(sprintf('Unsupported event type "%s".', $event->event_type));
        }

        foreach ($event->quantity_event_data as $eventData) {
            [$productId, $combinationId, $customizationId] = array_map('intval', explode('.', $eventData->product_id));
            $quantity = (int) $eventData->quantity->quantity;

            if (null !== $error = $this->updateCartQuantity($cart, $productId, $combinationId, $customizationId, $quantity)) {
                return BasketNotice::error($error);
            }
        }

        return null;
    }

    private function updateCartQuantity(\Cart $cart, int $productId, int $combinationId, int $customizationId, int $quantity): ?string
    {
        $currentQuantity = $this->getCurrentQuantity($cart, $productId, $combinationId, $customizationId);

        if (0 === $currentQuantity) {
            return 0 >= $quantity ? null : $this->module->l('Product is no longer in your cart.', self::TRANSLATION_SOURCE);
        }

        if (0 >= $quantity) {
            return $this->deleteProduct($cart, $productId, $combinationId, $customizationId);
        }

        if (0 === $deltaQuantity = $quantity - $currentQuantity) {
            return null;
        }

        if (null !== $error = $this->checkQuantity($cart, $productId, $combinationId, $customizationId, $quantity)) {
            return $error;
        }

        try {
            $result = $cart->updateQty(
                abs($deltaQuantity),
                $productId,
                $combinationId,
                $customizationId,
                $deltaQuantity > 0 ? 'up' : 'down',
                0,
                $this->context->shop,
                false
            );
        } catch (\Exception $e) {
            Logger::log(sprintf('Quantity update error: "%s" at %s:%d', $e->getMessage(), $e->getFile(), $e->getLine()));

            $result = false;
        }

        if (false === $result) {
            isset($e) || Logger::log(sprintf('Could not update product [%d-%d-%d] quantity in cart #%d.', $productId, $combinationId, $customizationId, $cart->id));

            return $this->module->l('Could not update product quantity.', self::TRANSLATION_SOURCE);
        }

        return null;
    }

    private function getCurrentQuantity(\Cart $cart, int $productId, int $combinationId, int $customizationId): int
    {
        foreach ($cart->getProducts() as $product) {
            if (
                $productId === (int) $product['id_product'] &&
                $combinationId === (int) $product['id_product_attribute'] &&
                $customizationId === (int) $product['id_customization']
            ) {
                return (int) $product['cart_quantity'];
            }
        }

        return 0;
    }

    private function deleteProduct(\Cart $cart, int $productId, int $combinationId, int $customizationId): ?string
    {
        try {
            $result = $cart->deleteProduct($productId, $combinationId, $customizationId);
        } catch (\Exception $e) {
            Logger::log(sprintf('Cart product deletion error: "%s" at %s:%d', $e->getMessage(), $e->getFile(), $e->getLine()));

            $result = false;
        }

        if (false === $result) {
            isset($e) || Logger::log(sprintf('Could not delete product [%d-%d-%d] from cart #%d.', $productId, $combinationId, $customizationId, $cart->id));

            return $this->module->l('Could delete the product from your cart.', self::TRANSLATION_SOURCE);
        }

        return null;
    }

    private function checkQuantity(\Cart $cart, int $productId, int $combinationId, int $customizationId, int $quantity): ?string
    {
        $product = new \Product($productId, false, $cart->id_lang);
        $minimalQuantity = 0 === $combinationId
            ? $product->minimal_quantity
            : (new \Combination($combinationId))->minimal_quantity;

        if ($quantity < $minimalQuantity) {
            return $this->translator->trans('The minimum purchase order quantity for the product %product% is %quantity%.', [
                '%product%' => $product->name,
                '%quantity%' => $minimalQuantity,
            ], 'Shop.Notifications.Error');
        }

        $availableQuantity = $this->getAvailableQuantity($cart, $productId, $combinationId, $customizationId);
        if (null !== $availableQuantity && $quantity > $availableQuantity) {
            return $this->translator->trans('The available purchase order quantity for this product is %quantity%.', [
                '%quantity%' => $availableQuantity,
            ], 'Shop.Notifications.Error');
        }

        return null;
    }

    private function getAvailableQuantity(\Cart $cart, int $productId, int $combinationId, int $customizationId): ?int
    {
        $outOfStock = \StockAvailable::outOfStock($productId);
        if (\Product::isAvailableWhenOutOfStock($outOfStock)) {
            return null;
        }

        return \Product::getQuantity($productId, $combinationId, null, $cart, $customizationId);
    }
}
