<?php

declare(strict_types=1);

namespace izi\prestashop\Hook;

/**
 * @method string[] getListenerNames(string $hookName)
 */
interface HookDispatcherInterface
{
    public function dispatch(string $name, array $parameters, ?int $shopId = null);
}
