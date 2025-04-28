<?php

namespace izi\prestashop\Validator\Cart;

use Symfony\Component\Validator\Constraint;

final class HasUnrestrictedProduct extends Constraint
{
    public $message = 'Cart has only restricted products';
}
