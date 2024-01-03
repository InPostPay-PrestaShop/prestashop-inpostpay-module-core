<?php

declare(strict_types=1);

namespace izi\prestashop\MerchantApi\Command;

use izi\prestashop\MerchantApi\Model\Basket\Request\BasketEvent;

final class UpdateBasketCommand
{
    /**
     * @var string
     */
    private $basketId;

    /**
     * @var BasketEvent
     */
    private $event;

    public function __construct(string $basketId, BasketEvent $event)
    {
        $this->basketId = $basketId;
        $this->event = $event;
    }

    public function getBasketId(): string
    {
        return $this->basketId;
    }

    public function getEvent(): BasketEvent
    {
        return $this->event;
    }
}
