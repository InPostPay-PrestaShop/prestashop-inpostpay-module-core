<?php

declare(strict_types=1);

namespace izi\prestashop\Validator\Product;

use Symfony\Component\Validator\Constraint;

final class NotInRestrictedCategory extends Constraint
{
    /**
     * @var int|null
     */
    public $shopId;

    public function getDefaultOption(): string
    {
        return 'shopId';
    }
}
