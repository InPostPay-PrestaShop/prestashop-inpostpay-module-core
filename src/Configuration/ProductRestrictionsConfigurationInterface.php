<?php

namespace izi\prestashop\Configuration;

use izi\prestashop\Product\Restriction\RestrictedAction;
use Symfony\Component\Validator\Constraint;

interface ProductRestrictionsConfigurationInterface
{
    /**
     * @return Constraint[] constraints that products in cart must be validated against to check if the restriction applies
     */
    public function getProductRestrictionConstraints(?int $shopId = null): array;

    public function getProductRestrictedAction(?int $shopId = null): RestrictedAction;
}
