<?php

declare(strict_types=1);

namespace izi\prestashop\Event;

final class OrderEvent extends Event
{
    public const BEFORE_UPDATE = 'inpostizi.order.before_update';
    public const UPDATED = 'inpostizi.order.updated';
    public const PERSISTED = 'inpostizi.order.persisted';

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
