<?php

declare(strict_types=1);

namespace izi\prestashop\Installer\Hook;

interface HooksProviderInterface
{
    /**
     * @return string[] hooks to register
     */
    public function getHookNames(): array;
}
