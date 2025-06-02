<?php

declare(strict_types=1);

namespace izi\prestashop\Analytics;

interface BasketAnalyticsInterface
{
    public function getGclid(): ?string;

    public function getFbclid(): ?string;

    public function getClientId(): ?string;

    public function isEmpty(): bool;
}
