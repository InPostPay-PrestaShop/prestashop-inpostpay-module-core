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

    /**
     * @var array<string, mixed>
     */
    private $options;

    /**
     * @param array<string, mixed> $options
     */
    public function __construct(string $psVersion = _PS_VERSION_, array $options = [])
    {
        $this->psVersion = $psVersion;
        $this->options = $options;
    }

    /**
     * {@inheritDoc}
     */
    public function getHookNames(): array
    {
        return HookExecutor::getHooksToInstall($this->psVersion, $this->options);
    }
}
