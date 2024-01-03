<?php

declare(strict_types=1);

namespace izi\prestashop\MerchantApi\Handler\Basket;

use izi\prestashop\Common\Basket\Notice;
use izi\prestashop\Entities\BasketInterface;
use izi\prestashop\MerchantApi\Model\Basket\Request\BasketEvent;
use izi\prestashop\MerchantApi\Model\Basket\Request\EventType;
use izi\prestashop\ObjectModel\ObjectManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Translation\TranslatorInterface as LegacyTranslatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class RelatedProductsEventHandler implements BasketEventHandlerInterface
{
    private const TRANSLATION_SOURCE = 'relatedproductseventhandler';

    /**
     * @var \Module
     */
    private $module;

    /**
     * @var \Context
     */
    private $context;

    /**
     * @var LegacyTranslatorInterface|TranslatorInterface
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

    public function __construct(\Module $module, \Context $context, ObjectManagerInterface $manager, LoggerInterface $logger)
    {
        $this->module = $module;
        $this->context = $context;
        $this->translator = $context->getTranslator();
        $this->manager = $manager;
        $this->logger = $logger;
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
            [$productId, $combinationId] = array_map('intval', explode('.', $relatedProduct->getProductId()));
            $quantity = (int) $relatedProduct->getQuantity()->getQuantity();

            if (null !== $error = $this->addRelatedProduct($cart, $productId, $combinationId, $quantity)) {
                return Notice::error($error);
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

        $product = $this->manager->getRepository(\Product::class)->find($productId, (int) $cart->id_lang);

        if (null === $product) {
            return $this->translator->trans('Product not found', [], 'Shop.Notifications.Error');
        }

        if (!$product->active || !$product->available_for_order || !$product->checkAccess((int) $cart->id_customer)) {
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
                $combinationId = \Product::getDefaultAttribute($productId); // TODO refactor static call
            }

            $combination = $this->manager->getRepository(\Combination::class)->find($combinationId);

            if (null === $combination) {
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
            $result = false;
            $this->logger->critical('Related product addition error: {error}', [
                'error' => $e,
            ]);
        }

        if (false === $result) {
            isset($e) || $this->logger->critical('Could not add product [{productId}] to cart #{cartId}.', [
                'productId' => implode('-', [$productId, $combinationId]),
                'cartId' => $cart->id,
            ]);

            return $this->module->l('Could not add the product to your cart.', self::TRANSLATION_SOURCE);
        }

        return null;
    }

    // TODO refactor static calls
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
