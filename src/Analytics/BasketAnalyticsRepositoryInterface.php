<?php

declare(strict_types=1);

namespace izi\prestashop\Analytics;

interface BasketAnalyticsRepositoryInterface
{
    public function find(int $id): ?BasketAnalyticsInterface;

    public function save(BasketAnalytics $basketAnalytics): void;

    public function remove(int $id): void;
}
