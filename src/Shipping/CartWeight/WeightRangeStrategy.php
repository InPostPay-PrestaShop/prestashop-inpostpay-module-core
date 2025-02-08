<?php

declare(strict_types=1);

namespace izi\prestashop\Shipping\CartWeight;

use izi\prestashop\Common\Weight;
use izi\prestashop\ObjectModel\Repository\ObjectRepositoryInterface;

final class WeightRangeStrategy implements CartWeightDeliveryStrategyInterface
{
    /**
     * @var CartWeightDeliveryStrategyInterface
     */
    private $genericStrategy;

    /**
     * @var ObjectRepositoryInterface<\RangeWeight>
     */
    private $rangeWeightRepository;

    public function __construct(CartWeightDeliveryStrategyInterface $genericStrategy, ObjectRepositoryInterface $rangeWeightRepository)
    {
        $this->genericStrategy = $genericStrategy;
        $this->rangeWeightRepository = $rangeWeightRepository;
    }

    public function isShippingAvailableBasedOnTotalWeight(\Carrier $carrier, Weight $cartWeight): bool
    {
        if (!$this->genericStrategy->isShippingAvailableBasedOnTotalWeight($carrier, $cartWeight)) {
            return false;
        }

        if (!$carrier->range_behavior || \Carrier::SHIPPING_METHOD_WEIGHT !== (int) $carrier->shipping_method) {
            return true;
        }

        $maxWeightRange = $this->rangeWeightRepository->getMaxWeightRangeByCarrier($carrier);

        if (null === $maxWeightRange) {
            return true;
        }

        return (new Weight((float) $maxWeightRange))->greaterThanOrEqual($cartWeight);
    }
}
