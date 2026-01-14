<?php

declare(strict_types=1);

namespace izi\prestashop\HotProduct;

use izi\prestashop\HotProduct\Exception\InvalidProductDataException;
use izi\prestashop\ObjectModel\Repository\ObjectRepositoryInterface;
use izi\prestashop\ObjectModel\Repository\ProductRepository;
use izi\prestashop\Product\ProductWithCombination;
use Symfony\Contracts\Translation\TranslatorInterface;

final class HotProductValidator
{
    /**
     * @var ProductRepository
     */
    private $productRepository;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @param ProductRepository $productRepository
     */
    public function __construct(ObjectRepositoryInterface $productRepository, TranslatorInterface $translator)
    {
        $this->productRepository = $productRepository;
        $this->translator = $translator;
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

    /**
     * @return ProductWithCombination associated with the hot product
     */
    public function validate(HotProduct $hotProduct, bool $allowDefaultCombination = false): ProductWithCombination
    {
        $shopId = $hotProduct->getShopId();

        $productWithCombination = $this->productRepository->findWithCombination(
            $hotProduct->getProductId(),
            $hotProduct->getCombinationId(),
            null,
            $shopId,
            $allowDefaultCombination
        );

        if (null === $productWithCombination) {
            throw new InvalidProductDataException($this->translator->trans('Product or combination does not exist.', [], 'Modules.Inpostizi.Validators'));
        }

        if (!self::isAvailableForOrder($productWithCombination->getProduct())) {
            throw new InvalidProductDataException($this->translator->trans('Product is inactive or not available for order.', [], 'Modules.Inpostizi.Validators'));
        }

        if (!self::hasEan($productWithCombination)) {
            throw new InvalidProductDataException($this->translator->trans('EAN code is required.', [], 'Modules.Inpostizi.Validators'));
        }

        return $productWithCombination;
    }

    private static function hasEan(ProductWithCombination $productWithCombination): bool
    {
        if ('' !== trim((string) $productWithCombination->getProduct()->ean13)) {
            return true;
        }

        if (null === $combination = $productWithCombination->getCombination()) {
            return false;
        }

        return '' !== trim((string) $combination->ean13);
    }
}
