<?php

declare(strict_types=1);

namespace izi\prestashop\Shipping\FreeDelivery;

use izi\prestashop\Configuration\PrestaShopConfiguration;
use izi\prestashop\ObjectModel\ObjectManagerInterface;

final class PriceRangeStrategy implements MinAmountCalculationStrategyInterface
{
    /**
     * @var MinAmountCalculationStrategyInterface
     */
    private $defaultStrategy;

    /**
     * @var PrestaShopConfiguration
     */
    private $configuration;

    /**
     * @var ObjectManagerInterface
     */
    private $manager;

    public function __construct(MinAmountCalculationStrategyInterface $defaultStrategy, PrestaShopConfiguration $configuration, ObjectManagerInterface $manager)
    {
        $this->defaultStrategy = $defaultStrategy;
        $this->configuration = $configuration;
        $this->manager = $manager;
    }

    public function getMinAmount(\Cart $cart, \Carrier $carrier): ?float
    {
        $minAmount = $this->defaultStrategy->getMinAmount($cart, $carrier);

        if (0. === $minAmount || \Carrier::SHIPPING_METHOD_PRICE !== $carrier->getShippingMethod()) {
            return $minAmount;
        }

        if ($carrier->shipping_handling && 0. !== $this->configuration->getShippingHandlingCost((int) $cart->id_shop)) {
            return $minAmount;
        }

        $country = $this->getDeliveryCountry();

        if (0. !== (float) $carrier->getMaxDeliveryPriceByPrice((int) $country->id_zone)) {
            return $minAmount;
        }

        $range = $this->getLastPriceRange((int) $carrier->id);
        $amount = (float) $range->delimiter1;

        return null === $minAmount ? $amount : min($minAmount, $amount);
    }

    private function getDeliveryCountry(): \Country
    {
        // as of writing this comment, only domestic delivery is available
        $country = $this->manager
            ->getRepository(\Country::class)
            ->findOneBy(['iso_code' => 'PL'], ['active' => 'DESC']);

        if (null !== $country) {
            return $country;
        }

        throw new \RuntimeException('Country "PL" does not exist.');
    }

    private function getLastPriceRange(int $carrierId): \RangePrice
    {
        $range = $this->manager
            ->getRepository(\RangePrice::class)
            ->findOneBy(['id_carrier' => $carrierId], ['delimiter1' => 'DESC']);

        if (null !== $range) {
            return $range;
        }

        throw new \RuntimeException('Price range not found.');
    }
}
