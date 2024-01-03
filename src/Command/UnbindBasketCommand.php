<?php

declare(strict_types=1);

namespace izi\prestashop\Command;

use izi\prestashop\Handler\UnbindBasketHandler;

/**
 * @see UnbindBasketHandler
 */
final class UnbindBasketCommand
{
    /**
     * @var int|string
     */
    private $basketId;

    /**
     * @var bool
     */
    private $orderCompleted;

    /**
     * @param int|string $basketId native basket ID
     */
    public function __construct($basketId, bool $orderCompleted = false)
    {
        $this->basketId = $basketId;
        $this->orderCompleted = $orderCompleted;
    }

    /**
     * @return int|string
     */
    public function getBasketId()
    {
        return $this->basketId;
    }

    public function isOrderCompleted(): bool
    {
        return $this->orderCompleted;
    }
}
