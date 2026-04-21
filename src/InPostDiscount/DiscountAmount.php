<?php

declare(strict_types=1);

namespace izi\prestashop\InPostDiscount;

final class DiscountAmount
{
    /**
     * @var float
     */
    private $net;

    /**
     * @var float
     */
    private $gross;

    public function __construct(float $net, float $gross)
    {
        $this->net = $net;
        $this->gross = $gross;
    }

    public function getNet(): float
    {
        return $this->net;
    }

    public function getGross(): float
    {
        return $this->gross;
    }
}
