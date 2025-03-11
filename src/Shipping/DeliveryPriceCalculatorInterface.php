<?php

declare(strict_types=1);

namespace izi\prestashop\Shipping;

use izi\prestashop\Common\Price;
use izi\prestashop\Common\PriceAmount;
use izi\prestashop\Configuration\DTO\Shipping\ServiceOptions;
use izi\prestashop\Shipping\Exception\UnavailableDeliveryOptionException;

interface DeliveryPriceCalculatorInterface
{
    /**
     * @throws UnavailableDeliveryOptionException if shipping the package with the provided carrier is not possible
     */
    public function getDeliveryPrice(\Cart $cart, \Carrier $carrier, ?array $productList = null): Price;

    public function getAdditionalServicePrice(\Cart $cart, \Carrier $carrier, ServiceOptions $options): Price;

    /**
     * @return PriceAmount|null gross amount required for free delivery or null if not applicable
     */
    public function getFreeDeliveryMinAmount(\Cart $cart, \Carrier $carrier): ?PriceAmount;
}
