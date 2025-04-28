<?php

namespace izi\prestashop\Configuration;

use Symfony\Component\Validator\Constraint;

interface ProductRestrictionsConfigurationInterface
{
    /**
     * @return Constraint[] constraints that products in cart must be validated against to check if ordering the product has not been restricted
     */
    public function getProductRestrictionConstraints(?int $shopId = null): array;
}
