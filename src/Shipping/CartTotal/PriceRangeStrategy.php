<?php

declare(strict_types=1);

namespace izi\prestashop\Shipping\CartTotal;

use izi\prestashop\Common\Price;
use izi\prestashop\ObjectModel\Repository\ObjectRepositoryInterface;
use izi\prestashop\ObjectModel\Repository\RangePriceRepository;

final class PriceRangeStrategy implements CartTotalDeliveryStrategyInterface
{
    /**
     * @var CartTotalDeliveryStrategyInterface
     */
    private $genericStrategy;

    /**
     * @var RangePriceRepository
     */
    private $rangePriceRepository;

    /**
     * @param RangePriceRepository $rangePriceRepository
     */
    public function __construct(CartTotalDeliveryStrategyInterface $genericStrategy, ObjectRepositoryInterface $rangePriceRepository)
    {
        $this->genericStrategy = $genericStrategy;
        $this->rangePriceRepository = $rangePriceRepository;
    }

    public function isShippingAvailableBasedOnTotalPrice(\Carrier $carrier, Price $cartTotal): bool
    {
        if ($this->genericStrategy->isShippingAvailableBasedOnTotalPrice($carrier, $cartTotal)) {
            return true;
        }

        $maxPriceRange = $this->rangePriceRepository->getMaxPriceRangeByCarrier($carrier);

        return null !== $maxPriceRange && $cartTotal->getGross() <= $maxPriceRange;
    }
}
