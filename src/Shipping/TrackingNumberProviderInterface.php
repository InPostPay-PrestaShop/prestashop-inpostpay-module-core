<?php

declare(strict_types=1);

namespace izi\prestashop\Shipping;

interface TrackingNumberProviderInterface
{
    /**
     * @return string[]
     */
    public function getTrackingNumbers(int $orderId): array;
}
