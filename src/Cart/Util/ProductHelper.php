<?php

declare(strict_types=1);

namespace izi\prestashop\Cart\Util;

final class ProductHelper
{
    public static function findProductInCart(\Cart $cart, int $productId, int $combinationId, int $customizationId = 0): ?array
    {
        foreach ($cart->getProducts() as $product) {
            if (self::isSameProduct($product, $productId, $combinationId, $customizationId)) {
                return $product;
            }
        }

        return null;
    }

    public static function isInCart(\Cart $cart, int $productId, int $combinationId, int $customizationId = 0): bool
    {
        return null !== self::findProductInCart($cart, $productId, $combinationId, $customizationId);
    }

    public static function getCartQuantity(\Cart $cart, int $productId, int $combinationId, int $customizationId = 0): int
    {
        if (null === $product = self::findProductInCart($cart, $productId, $combinationId, $customizationId)) {
            return 0;
        }

        return (int) $product['cart_quantity'];
    }

    private static function isSameProduct(array $cartProduct, int $productId, int $combinationId, int $customizationId): bool
    {
        return $productId === (int) $cartProduct['id_product']
            && $combinationId === (int) $cartProduct['id_product_attribute']
            && $customizationId === (int) $cartProduct['id_customization'];
    }
}
