<?php

declare(strict_types=1);

namespace izi\prestashop\Configuration;

use izi\prestashop\Configuration\DTO\Consent;

interface ConsentsConfigurationInterface
{
    /**
     * @return Consent[]
     */
    public function getConsents(?int $shopId = null): array;
}
