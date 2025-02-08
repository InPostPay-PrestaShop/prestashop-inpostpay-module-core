<?php

declare(strict_types=1);

namespace izi\prestashop\Command;

use izi\prestashop\Handler\GetBindingConfirmationHandler;

/**
 * @see GetBindingConfirmationHandler
 *
 * @deprecated
 */
final class GetBindingConfirmationCommand
{
    /**
     * @var int|string|null
     */
    private $basketId;

    /**
     * @param int|string|null $basketId native basket ID
     */
    public function __construct($basketId)
    {
        $this->basketId = $basketId;
    }

    /**
     * @return int|string|null
     */
    public function getBasketId()
    {
        return $this->basketId;
    }
}
