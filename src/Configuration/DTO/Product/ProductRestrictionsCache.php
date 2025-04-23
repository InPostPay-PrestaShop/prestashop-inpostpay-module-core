<?php

declare(strict_types=1);

namespace izi\prestashop\Configuration\DTO\Product;

use izi\prestashop\Product\ProductType;

final class ProductRestrictionsCache implements \JsonSerializable
{
    /**
     * @var ProductType[]
     */
    private $productTypes = [];

    /**
     * @var bool
     */
    private $hasCategoryRestrictions = false;

    /**
     * @var bool
     */
    private $hasManufacturerRestrictions = false;

    /**
     * @var bool
     */
    private $hasAttributeGroupRestrictions = false;

    /**
     * @var bool
     */
    private $hasFeatureRestrictions = false;

    public static function fromRestrictions(ProductRestrictions $restrictions): self
    {
        return (new self())
            ->setProductTypes($restrictions->getProductTypes())
            ->setHasCategoryRestrictions([] !== $restrictions->getCategoryIds())
            ->setHasManufacturerRestrictions([] !== $restrictions->getManufacturerIds())
            ->setHasAttributeGroupRestrictions([] !== $restrictions->getAttributeGroupIds())
            ->setHasFeatureRestrictions([] !== $restrictions->getFeatureIds());
    }

    public function getProductTypes(): array
    {
        return $this->productTypes;
    }

    /**
     * @param ProductType[] $productTypes
     */
    public function setProductTypes(array $productTypes): self
    {
        $this->productTypes = $productTypes;

        return $this;
    }

    public function hasCategoryRestrictions(): bool
    {
        return $this->hasCategoryRestrictions;
    }

    public function setHasCategoryRestrictions(bool $hasCategoryRestrictions): self
    {
        $this->hasCategoryRestrictions = $hasCategoryRestrictions;

        return $this;
    }

    public function hasManufacturerRestrictions(): bool
    {
        return $this->hasManufacturerRestrictions;
    }

    public function setHasManufacturerRestrictions(bool $hasManufacturerRestrictions): self
    {
        $this->hasManufacturerRestrictions = $hasManufacturerRestrictions;

        return $this;
    }

    public function hasAttributeGroupRestrictions(): bool
    {
        return $this->hasAttributeGroupRestrictions;
    }

    public function setHasAttributeGroupRestrictions(bool $hasAttributeGroupRestrictions): self
    {
        $this->hasAttributeGroupRestrictions = $hasAttributeGroupRestrictions;

        return $this;
    }

    public function hasFeatureRestrictions(): bool
    {
        return $this->hasFeatureRestrictions;
    }

    public function setHasFeatureRestrictions(bool $hasFeatureRestrictions): ProductRestrictionsCache
    {
        $this->hasFeatureRestrictions = $hasFeatureRestrictions;

        return $this;
    }

    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
