<?php

declare(strict_types=1);

namespace izi\prestashop\Installer;

use izi\prestashop\Installer\Exception\CoreInstallationException;

final class CoreInstaller implements InstallerInterface, UninstallerInterface
{
    public function install(\Module $module): void
    {
        $installer = \Closure::bind(function () {
            /* @phpstan-ignore class.noParent */
            return parent::install();
        }, $module, \get_class($module));

        if ($installer()) {
            return;
        }

        throw CoreInstallationException::create($module);
    }

    public function uninstall(\Module $module, bool $keepData = false): void
    {
        $uninstaller = \Closure::bind(function () {
            /* @phpstan-ignore class.noParent */
            return parent::uninstall();
        }, $module, \get_class($module));

        if ($uninstaller()) {
            return;
        }

        throw CoreInstallationException::create($module);
    }
}
