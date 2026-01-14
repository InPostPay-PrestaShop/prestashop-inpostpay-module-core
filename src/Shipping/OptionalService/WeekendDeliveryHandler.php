<?php

declare(strict_types=1);

namespace izi\prestashop\Shipping\OptionalService;

use izi\prestashop\Common\Delivery\DeliveryType;

final class WeekendDeliveryHandler implements OptionalServiceHandlerInterface
{
    /**
     * @var ShippingCostAdjuster
     */
    private $costAdjuster;

    public function __construct(ShippingCostAdjuster $costAdjuster)
    {
        $this->costAdjuster = $costAdjuster;
    }

    public function supports(string $serviceCode): bool
    {
        return 'PWW' === $serviceCode;
    }

    public function handle(\Cart $cart, string $serviceCode, DeliveryType $deliveryType, bool $selected): void
    {
        if ('PWW' !== $serviceCode) {
            throw new \DomainException(\sprintf('Unsupported service "%s".', $serviceCode));
        }

        if (!$selected) {
            return;
        }

        $this->costAdjuster->addServiceCost($deliveryType, $serviceCode);
    }
}
