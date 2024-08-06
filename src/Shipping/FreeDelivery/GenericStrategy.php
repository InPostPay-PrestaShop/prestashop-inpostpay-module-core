<?php

declare(strict_types=1);

namespace izi\prestashop\Shipping\FreeDelivery;

use izi\prestashop\Configuration\PrestaShopConfiguration;

final class GenericStrategy implements MinAmountCalculationStrategyInterface
{
    /**
     * @var PrestaShopConfiguration
     */
    private $configuration;

    public function __construct(PrestaShopConfiguration $configuration)
    {
        $this->configuration = $configuration;
    }

    public function getMinAmount(\Cart $cart, \Carrier $carrier): ?float
    {
        if (\Carrier::SHIPPING_METHOD_FREE === $carrier->getShippingMethod()) {
            return 0.;
        }

        $shopId = (int) $cart->id_shop;

        if (0. >= $minAmount = $this->configuration->getFreeDeliveryMinAmount($shopId)) {
            return null;
        }

        return $minAmount;
    }
}
