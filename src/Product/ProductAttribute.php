<?php

declare(strict_types=1);

namespace izi\prestashop\Product;

/**
 * @template T of (\ProductAttribute|\Attribute)
 */
final class ProductAttribute
{
    /**
     * @var T
     */
    private $attribute;

    /**
     * @var \AttributeGroup
     */
    private $group;

    /**
     * @param T $attribute
     *
     * @internal
     */
    public function __construct(\ObjectModel $attribute, \AttributeGroup $group)
    {
        if ((int) $attribute->id_attribute_group !== (int) $group->id) {
            throw new \InvalidArgumentException('Attribute does not belong to the given group.');
        }

        $this->attribute = $attribute;
        $this->group = $group;
    }

    /**
     * @return T
     */
    public function getAttribute(): \ObjectModel
    {
        return $this->attribute;
    }

    public function getGroup(): \AttributeGroup
    {
        return $this->group;
    }
}
