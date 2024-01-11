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

final class ProductsQuantityEventHandler implements BasketEventHandlerInterface
{
    private const TRANSLATION_SOURCE = 'productsquantityeventhandler';

    /**
     * @var \Module
     */
    private $module;

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
        $this->translator = $context->getTranslator();
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
            throw new \DomainException(sprintf('Unsupported event type "%s".', $type->value));
        }

        $cart = $basket->getEntity();

        if (!$cart instanceof \Cart) {
            throw new \InvalidArgumentException(sprintf('Expected basket entity to be an instance of "%s", "%s" given.', \Cart::class, get_class($cart)));
        }

        foreach ($event->getQuantityEventData() as $data) {
            [$productId, $combinationId, $customizationId] = array_map('intval', explode('.', $data->getProductId()));
            $quantity = (int) $data->getQuantity()->getQuantity();

            if (null !== $error = $this->updateCartQuantity($cart, $productId, $combinationId, $customizationId, $quantity)) {
                return Notice::error($error);
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
                null,
                false
            );
        } catch (\Exception $e) {
            $result = false;
            $this->logger->critical('Quantity update error: {error}', [
                'error' => $e,
            ]);
        }

        if (false === $result) {
            isset($e) || $this->logger->critical('Could not update product [{productId}] quantity in cart #{cartId}.', [
                'productId' => implode('-', [$productId, $combinationId, $customizationId]),
                'cartId' => $cart->id,
            ]);

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
            $result = false;
            $this->logger->critical('Cart product deletion error: {error}', [
                'error' => $e,
            ]);
        }

        if (false === $result) {
            isset($e) || $this->logger->critical('Could not delete product [{productId}] from cart #{cartId}.', [
                'productId' => implode('-', [$productId, $combinationId, $customizationId]),
                'cartId' => $cart->id,
            ]);

            return $this->module->l('Could delete the product from your cart.', self::TRANSLATION_SOURCE);
        }

        return null;
    }

    private function checkQuantity(\Cart $cart, int $productId, int $combinationId, int $customizationId, int $quantity): ?string
    {
        $product = $this->manager->getRepository(\Product::class)->find($productId, (int) $cart->id_lang);

        if (null === $product) {
            throw new \RuntimeException('Product does not exist');
        }

        $combination = 0 !== $combinationId
            ? $this->manager->getRepository(\Combination::class)->find($combinationId)
            : null;

        $minimalQuantity = null === $combination
            ? $product->minimal_quantity
            : $combination->minimal_quantity;

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

    // TODO refactor static calls
    private function getAvailableQuantity(\Cart $cart, int $productId, int $combinationId, int $customizationId): ?int
    {
        $outOfStock = \StockAvailable::outOfStock($productId);
        if (\Product::isAvailableWhenOutOfStock($outOfStock)) {
            return null;
        }

        return \Product::getQuantity($productId, $combinationId, null, $cart, $customizationId);
    }
}
