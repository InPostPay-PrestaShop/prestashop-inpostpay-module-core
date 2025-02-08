<?php

declare(strict_types=1);

namespace izi\prestashop\Command;

use izi\prestashop\Entities\BasketInterface;
use izi\prestashop\Handler\GetBasketBindingKeyHandler;

/**
 * @see GetBasketBindingKeyHandler
 */
final class GetBasketBindingKeyCommand
{
    /**
     * @var BasketInterface
     */
    private $basket;

    /**
     * @var bool
     */
    private $refresh;

    /**
     * @param bool $refresh if true, the binding key will be fetched from the API even if it's already stored in the session
     */
    public function __construct(BasketInterface $basket, bool $refresh = false)
    {
        $this->basket = $basket;
        $this->refresh = $refresh;
    }

    public function getBasket(): BasketInterface
    {
        return $this->basket;
    }

    public function isRefresh(): bool
    {
        return $this->refresh;
    }
}
