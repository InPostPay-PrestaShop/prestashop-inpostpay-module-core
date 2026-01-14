<?php

declare(strict_types=1);

namespace izi\prestashop\Installer\Hook;

use izi\prestashop\Hook\HookExecutor;

/**
 * @internal
 */
final class HooksProvider implements HooksProviderInterface
{
    /**
     * @var string
     */
    private $psVersion;

    public function __construct(string $psVersion = _PS_VERSION_)
    {
        $this->psVersion = $psVersion;
    }

    /**
     * {@inheritDoc}
     */
    public function getHookNames(): array
    {
        return HookExecutor::getHooksToInstall($this->psVersion);
    }
}
