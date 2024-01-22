<?php

declare(strict_types=1);

namespace izi\prestashop\Configuration;

interface ThankYouWidgetConfigurationInterface
{
    public function shouldDisplayHook($hookName): bool;
}
