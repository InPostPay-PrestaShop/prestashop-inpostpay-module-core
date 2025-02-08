<?php

declare(strict_types=1);

namespace izi\prestashop\Validator\Cart;

use Symfony\Component\Validator\Constraint;

final class HasProducts extends Constraint
{
    /**
     * @var string
     */
    public $message = 'Cart is empty';
}
