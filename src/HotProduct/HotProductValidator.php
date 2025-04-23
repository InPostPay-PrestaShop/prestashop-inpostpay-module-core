<?php

declare(strict_types=1);

namespace izi\prestashop\HotProduct;

use izi\prestashop\HotProduct\Exception\InvalidProductDataException;
use izi\prestashop\ObjectModel\Repository\ObjectRepositoryInterface;
use izi\prestashop\ObjectModel\Repository\ProductRepository;

final class HotProductValidator
{
    /**
     * @var ProductRepository
     */
    private $productRepository;

    /**
     * @param ProductRepository $productRepository
     */
    public function __construct(ObjectRepositoryInterface $productRepository)
    {
        $this->productRepository = $productRepository;
    }

    /**
     * @internal
     */
    public static function isAvailableForOrder(\Product $product): bool
    {
        if (!$product->active) {
            return false;
        }

        if (!$product->available_for_order) {
            return false;
        }

        if (2 === (int) $product->customizable) {
            return false;
        }

        if (\Product::STATE_SAVED !== (int) $product->state) {
            return false;
        }

        return true;
    }

    public function validate(HotProduct $hotProduct): void
    {
        $shopId = $hotProduct->getShopId();

        $productWithCombination = $this->productRepository->findWithCombination(
            $hotProduct->getProductId(),
            $hotProduct->getCombinationId(),
            null,
            $shopId
        );

        if (null === $productWithCombination) {
            throw new InvalidProductDataException('Product or combination does not exist.');
        }

        if (!self::isAvailableForOrder($productWithCombination->getProduct())) {
            throw new InvalidProductDataException('Product is inactive or not available for order.');
        }
    }
}
