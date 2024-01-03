<?php

declare(strict_types=1);

namespace izi\prestashop\Hook;

interface HookInterface
{
    public static function getHookName(): string;

    public function execute(array $parameters);
}
