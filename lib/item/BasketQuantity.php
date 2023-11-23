<?php

namespace izi\item;


use izi\item\order\OrderQuantity;

class BasketQuantity extends Quantity
{
    /**
     * @var int|float|null
     */
    protected $available_quantity;

    /**
     * @var int|float|null
     */
    protected $max_quantity;

    public function asOrderQuantity(): OrderQuantity
    {
        $quantity = new OrderQuantity();
        $quantity->quantity = $this->quantity;
        $quantity->quantity_unit = $this->quantity_unit;
        $quantity->quantity_type = $this->quantity_type;

        return $quantity;
    }
}
