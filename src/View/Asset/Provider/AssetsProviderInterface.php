<?php

declare(strict_types=1);

namespace izi\prestashop\View\Asset\Provider;

use izi\prestashop\View\Asset\Provider\DTO\Assets;

interface AssetsProviderInterface
{
    public function getAssets(): ?Assets;
}
