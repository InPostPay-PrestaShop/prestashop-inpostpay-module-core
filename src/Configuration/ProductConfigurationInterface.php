<?php

declare(strict_types=1);

namespace izi\prestashop\Configuration;

interface ProductConfigurationInterface
{
    public function getNormalImageTypeId(?int $shopId = null): ?int;

    public function getSmallImageTypeId(?int $shopId = null): ?int;

    public function getLargeImageTypeId(?int $shopId = null): ?int;
}
