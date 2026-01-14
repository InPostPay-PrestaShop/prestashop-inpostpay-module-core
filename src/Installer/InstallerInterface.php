<?php

declare(strict_types=1);

namespace izi\prestashop\Installer;

use izi\prestashop\Installer\Exception\InstallerException;

interface InstallerInterface
{
    /**
     * @throws InstallerException
     */
    public function install(\Module $module): void;
}
