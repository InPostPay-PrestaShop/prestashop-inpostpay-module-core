<?php

declare(strict_types=1);

namespace izi\prestashop\Analytics\Factory;

use izi\prestashop\Analytics\BasketAnalyticsInterface;
use Symfony\Component\HttpFoundation\Request;

interface BasketAnalyticsFactoryInterface
{
    public function createFromRequest(Request $request): BasketAnalyticsInterface;
}
