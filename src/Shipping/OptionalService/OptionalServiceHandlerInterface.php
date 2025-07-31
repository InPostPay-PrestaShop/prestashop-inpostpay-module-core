<?php

declare(strict_types=1);

namespace izi\prestashop\Shipping\OptionalService;

use izi\prestashop\Common\Delivery\DeliveryType;
use izi\prestashop\Shipping\OptionalService\Exception\ServiceUnavailableException;

interface OptionalServiceHandlerInterface
{
    public function supports(string $serviceCode): bool;

    /**
     * @throws ServiceUnavailableException
     */
    public function handle(\Cart $cart, string $serviceCode, DeliveryType $deliveryType, bool $selected);
}
