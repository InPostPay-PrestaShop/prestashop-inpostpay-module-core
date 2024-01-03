<?php

declare(strict_types=1);

namespace izi\prestashop\Hook\Adapter;

use izi\prestashop\Hook\HookDispatcherInterface;

final class HookDispatcher implements HookDispatcherInterface
{
    public function dispatch(string $name, array $parameters, int $shopId = null)
    {
        return \Hook::exec($name, $parameters, null, false, true, false, $shopId);
    }
}
