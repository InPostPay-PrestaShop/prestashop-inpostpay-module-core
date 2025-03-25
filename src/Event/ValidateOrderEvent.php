<?php

namespace izi\prestashop\Event;

class ValidateOrderEvent extends Event
{
    /**
     * @var \Order
     */
    private $order;

    public function __construct(\Order $order)
    {
        $this->order = $order;
    }

    public function getOrder(): \Order
    {
        return $this->order;
    }
}
