<?php

declare(strict_types=1);

namespace izi\prestashop\Analytics\Command;

use izi\prestashop\Analytics\BasketAnalyticsInterface;

/**
 * @see UpdateCartAnalyticsHandler
 */
final class UpdateCartAnalyticsCommand
{
    /**
     * @var int
     */
    private $cartId;

    /**
     * @var BasketAnalyticsInterface
     */
    private $basketAnalytics;

    public function __construct(int $cartId, BasketAnalyticsInterface $basketAnalytics)
    {
        $this->cartId = $cartId;
        $this->basketAnalytics = $basketAnalytics;
    }

    public function getCartId(): int
    {
        return $this->cartId;
    }

    public function getBasketAnalytics(): BasketAnalyticsInterface
    {
        return $this->basketAnalytics;
    }
}
