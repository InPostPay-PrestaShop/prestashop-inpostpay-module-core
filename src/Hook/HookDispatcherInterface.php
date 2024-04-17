<?php

declare(strict_types=1);

namespace izi\prestashop\Hook;

interface HookDispatcherInterface
{
    public function dispatch(string $name, array $parameters, ?int $shopId = null);
}
