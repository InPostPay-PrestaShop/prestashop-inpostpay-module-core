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

    public function __construct(BasketInterface $basket)
    {
        $this->basket = $basket;
    }

    public function getBasket(): BasketInterface
    {
        return $this->basket;
    }
}
