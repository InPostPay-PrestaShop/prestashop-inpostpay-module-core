<?php

declare(strict_types=1);

namespace izi\prestashop\Installer;

use izi\prestashop\Hook\Adapter\HookDispatcher;
use izi\prestashop\Hook\HookDispatcherInterface;

final class HookDispatchingUninstaller implements UninstallerInterface
{
    /**
     * @var HookDispatcherInterface
     */
    private $dispatcher;

    /**
     * @var string
     */
    private $psVersion;

    public function __construct(?HookDispatcherInterface $dispatcher = null, string $psVersion = _PS_VERSION_)
    {
        $this->dispatcher = $dispatcher ?? new HookDispatcher();
        $this->psVersion = $psVersion;
    }

    public function uninstall(\Module $module, bool $keepData = true): void
    {
        if (\Tools::version_compare($this->psVersion, '1.7.8', '>=')) {
            return;
        }

        $this->dispatcher->dispatch('actionModuleUninstallAfter', ['object' => $module]);
    }
}
