<?php

namespace izi\item;

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
}
