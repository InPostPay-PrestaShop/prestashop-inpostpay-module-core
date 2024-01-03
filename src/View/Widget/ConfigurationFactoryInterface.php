<?php

declare(strict_types=1);

namespace izi\prestashop\View\Widget;

interface ConfigurationFactoryInterface
{
    public function createForCheckout(): Configuration;

    public function createForCartPage(): ?Configuration;

    public function createForProductCard(int $productId): ?Configuration;
}
