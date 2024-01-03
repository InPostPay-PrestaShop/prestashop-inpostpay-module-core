<?php

declare(strict_types=1);

namespace izi\prestashop\Handler\Result;

use izi\prestashop\Http\Response\ServerSentEvent;
use izi\prestashop\MerchantApi\Model\Basket\Request\BindingConfirmation;

final class BindingConfirmationStream
{
    /**
     * @var string
     */
    private $basketId;

    /**
     * @var \Generator<ServerSentEvent<BindingConfirmation>>
     */
    private $events;

    /**
     * @param \Generator<ServerSentEvent<BindingConfirmation>> $events
     */
    public function __construct(?string $basketId, \Generator $events)
    {
        $this->basketId = $basketId;
        $this->events = $events;
    }

    public function getBasketId(): ?string
    {
        return $this->basketId;
    }

    /**
     * @return iterable<ServerSentEvent<BindingConfirmation>>
     */
    public function getEvents(): iterable
    {
        return $this->events;
    }
}
