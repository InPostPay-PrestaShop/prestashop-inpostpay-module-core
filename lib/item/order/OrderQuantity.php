<?php

namespace izi\item\order;

use izi\item\BasketQuantity;
use izi\item\Quantity;

class OrderQuantity extends Quantity
{
    /**
     * @param BasketQuantity|\stdClass $basketQuantity
     */
    public static function fromBasketQuantity($basketQuantity): self
    {
        $quantity = new self();

        $quantity->quantity = $basketQuantity->quantity;
        $quantity->quantity_unit = $basketQuantity->quantity_unit;
        $quantity->quantity_type = $basketQuantity->quantity_type;

        return $quantity;
    }
}
