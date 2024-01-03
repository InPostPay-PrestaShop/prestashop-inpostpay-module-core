<?php

declare(strict_types=1);

namespace izi\prestashop\Hook;

interface HookExecutorInterface
{
    /**
     * @return mixed hook result
     */
    public function execute(string $hookName, array $parameters);
}
