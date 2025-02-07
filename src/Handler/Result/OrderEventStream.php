<?php

declare(strict_types=1);

namespace izi\prestashop\Handler\Result;

use izi\prestashop\Http\Response\ServerSentEvent;
use izi\prestashop\MerchantApi\Model\Basket\Request\BindingConfirmation;

/**
 * @deprecated
 */
final class OrderEventStream
{
    /**
     * @var \Generator<ServerSentEvent<BindingConfirmation>>
     */
    private $events;

    /**
     * @param \Generator<ServerSentEvent<OrderEvent>> $events
     */
    public function __construct(\Generator $events)
    {
        $this->events = $events;
    }

    /**
     * @return iterable<ServerSentEvent<OrderEvent>>
     */
    public function getEvents(): iterable
    {
        return $this->events;
    }
}
