<?php

namespace izi\item;

use izi\item\order\OrderProduct;

class BasketProduct extends Product
{
    /**
     * @var Price|null
     */
    protected $promo_price;

    /**
     * @var Price|null
     */
    protected $lowest_price;

    /**
     * @var BasketQuantity
     */
    protected $quantity;
}
