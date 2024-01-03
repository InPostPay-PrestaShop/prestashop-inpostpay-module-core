<?php

namespace izi\prestashop\Handler;

use izi\item\BasketNotice;
use izi\prestashop\Logger;

final class RelatedProductsEventHandler implements BasketEventHandlerInterface
{
    public const EVENT_TYPE = 'RELATED_PRODUCTS';
    private const TRANSLATION_SOURCE = 'relatedproductseventhandler';

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

        foreach ($event->related_products_event_data as $eventData) {
            [$productId, $combinationId] = array_map('intval', explode('.', $eventData->product_id));
            $quantity = (int) $eventData->quantity->quantity;

            if (null !== $error = $this->addRelatedProduct($cart, $productId, $combinationId, $quantity)) {
                return BasketNotice::error($error);
            }
        }

        return null;
    }

    private function addRelatedProduct(\Cart $cart, int $productId, int $combinationId, int $quantity): ?string
    {
        if ($this->isInCart($cart, $productId, $combinationId)) {
            return null;
        }

        if (0 >= $quantity) {
            return $this->translator->trans('Null quantity.', [], 'Shop.Notifications.Error');
        }

        if (0 >= $productId || !\Validate::isLoadedObject($product = new \Product($productId, false, $cart->id_lang))) {
            return $this->translator->trans('Product not found', [], 'Shop.Notifications.Error');
        }

        if (!$product->active || !$product->available_for_order || !$product->checkAccess($cart->id_customer)) {
            return $this->translator->trans('This product (%product%) is no longer available.', [
                '%product%' => $product->name,
            ], 'Shop.Notifications.Error');
        }

        if (2 === (int) $product->customizable) {
            return $this->module->l('This product requires customization. Please add it to your cart via the shop page.', self::TRANSLATION_SOURCE);
        }

        if (!$product->hasAttributes()) {
            $combinationId = 0;
            $minimalQuantity = $product->minimal_quantity;
        } else {
            if (0 === $combinationId) {
                $combinationId = \Product::getDefaultAttribute($productId);
            }

            if (!\Validate::isLoadedObject($combination = new \Combination($combinationId))) {
                return $this->translator->trans('Product not found', [], 'Shop.Notifications.Error');
            }

            $minimalQuantity = $combination->minimal_quantity;
        }

        if ($quantity < $minimalQuantity) {
            return $this->translator->trans('The minimum purchase order quantity for the product %product% is %quantity%.', [
                '%product%' => $product->name,
                '%quantity%' => $minimalQuantity,
            ], 'Shop.Notifications.Error');
        }

        $availableQuantity = $this->getAvailableQuantity($productId, $combinationId);
        if (null !== $availableQuantity && $quantity > $availableQuantity) {
            return $this->translator->trans('The available purchase order quantity for this product is %quantity%.', [
                '%quantity%' => $availableQuantity,
            ], 'Shop.Notifications.Error');
        }

        try {
            $result = $cart->updateQty(
                $quantity,
                $productId,
                $combinationId,
                0,
                'up',
                0,
                $this->context->shop
            );
        } catch (\Exception $e) {
            Logger::log(sprintf('Related product addition error: "%s" at %s:%d', $e->getMessage(), $e->getFile(), $e->getLine()));

            $result = false;
        }

        if (false === $result) {
            isset($e) || Logger::log(sprintf('Could not add product [%d-%d] to cart #%d.', $productId, $combinationId, $cart->id));

            return $this->module->l('Could not add the product to your cart.', self::TRANSLATION_SOURCE);
        }

        return null;
    }

    private function getAvailableQuantity(int $productId, int $combinationId): ?int
    {
        $outOfStock = \StockAvailable::outOfStock($productId);
        if (\Product::isAvailableWhenOutOfStock($outOfStock)) {
            return null;
        }

        return \StockAvailable::getQuantityAvailableByProduct($productId, $combinationId);
    }

    private function isInCart(\Cart $cart, int $productId, int $combinationId): bool
    {
        foreach ($cart->getProducts() as $cartProduct) {
            if (
                $productId === (int) $cartProduct['id_product'] &&
                $combinationId === (int) $cartProduct['id_product_attribute'] &&
                0 === $cartProduct['id_customization']
            ) {
                return true;
            }
        }

        return false;
    }
}
