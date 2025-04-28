<?php

namespace izi\prestashop\Validator\Product;

use Symfony\Component\Validator\Constraint;

final class Unrestricted extends Constraint
{
    public $message = 'Product is restricted';

    /**
     * @var int|null
     */
    public $shopId;

    public function getDefaultOption(): string
    {
        return 'shopId';
    }
}
