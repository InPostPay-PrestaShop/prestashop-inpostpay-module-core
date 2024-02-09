<?php

declare(strict_types=1);

namespace izi\prestashop\Configuration;

interface AdvancedConfigurationInterface
{
    public function isDebugEnabled(): bool;
}
