<?php

declare(strict_types=1);

namespace izi\prestashop\Command;

use izi\prestashop\Handler\GetOrderEventsHandler;

/**
 * @see GetOrderEventsHandler
 *
 * @deprecated
 */
final class GetOrderEventsCommand
{
    /**
     * @var string
     */
    private $basketId;

    /**
     * @param string $basketId
     */
    public function __construct(string $basketId)
    {
        $this->basketId = $basketId;
    }

    public function getBasketId(): string
    {
        return $this->basketId;
    }
}
