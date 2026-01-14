<?php

declare(strict_types=1);

namespace izi\prestashop\Configuration\DTO\Product;

use izi\prestashop\Product\ProductType;
use izi\prestashop\Product\Restriction\RestrictedAction;
use Symfony\Component\Validator\Constraints as Assert;

final class ProductRestrictions
{
    /**
     * @var ProductType[]
     *
     * @Assert\All(
     *     @Assert\Type(ProductType::class),
     * )
     */
    private $productTypes = [];

    /**
     * @var int[] {@see \Category::$id}
     */
    private $categoryIds = [];

    /**
     * @var int[] {@see \Manufacturer::$id}
     */
    private $manufacturerIds = [];

    /**
     * @var int[] {@see \AttributeGroup::$id}
     */
    private $attributeGroupIds = [];

    /**
     * @var int[] {@see \Feature::$id}
     */
    private $featureIds = [];

    /**
     * @var RestrictedAction
     *
     * @Assert\NotNull()
     */
    private $restrictedAction;

    /**
     * @return ProductType[]
     */
    public function getProductTypes(): array
    {
        return $this->productTypes;
    }

    /**
     * @param ProductType[] $productTypes
     *
     * @return $this
     */
    public function setProductTypes(array $productTypes): self
    {
        $this->productTypes = $productTypes;

        return $this;
    }

    /**
     * @return int[]
     */
    public function getCategoryIds(): array
    {
        return $this->categoryIds;
    }

    /**
     * @param int[] $categoryIds
     *
     * @return $this
     */
    public function setCategoryIds(array $categoryIds): self
    {
        $this->categoryIds = $categoryIds;

        return $this;
    }

    /**
     * @return int[]
     */
    public function getManufacturerIds(): array
    {
        return $this->manufacturerIds;
    }

    /**
     * @param int[] $manufacturerIds
     *
     * @return $this
     */
    public function setManufacturerIds(array $manufacturerIds): self
    {
        $this->manufacturerIds = $manufacturerIds;

        return $this;
    }

    /**
     * @return int[]
     */
    public function getAttributeGroupIds(): array
    {
        return $this->attributeGroupIds;
    }

    /**
     * @param int[] $attributeGroupIds
     *
     * @return $this
     */
    public function setAttributeGroupIds(array $attributeGroupIds): self
    {
        $this->attributeGroupIds = $attributeGroupIds;

        return $this;
    }

    /**
     * @return int[]
     */
    public function getFeatureIds(): array
    {
        return $this->featureIds;
    }

    /**
     * @param int[] $featureIds
     *
     * @return $this
     */
    public function setFeatureIds(array $featureIds): self
    {
        $this->featureIds = $featureIds;

        return $this;
    }

    public function getRestrictedAction(): ?RestrictedAction
    {
        return $this->restrictedAction;
    }

    /**
     * @return $this
     */
    public function setRestrictedAction(?RestrictedAction $restrictedAction): self
    {
        $this->restrictedAction = $restrictedAction;

        return $this;
    }
}
