<?php

declare(strict_types=1);

namespace izi\prestashop\Command;

use izi\prestashop\Handler\UpdateBasketHandler;

/**
 * @see UpdateBasketHandler
 */
final class UpdateBasketCommand
{
    /**
     * @var int|string
     */
    private $basketId;

    /**
     * @param int|string $basketId native basket ID
     */
    public function __construct($basketId)
    {
        $this->basketId = $basketId;
    }

    /**
     * @return int|string
     */
    public function getBasketId()
    {
        return $this->basketId;
    }
}
