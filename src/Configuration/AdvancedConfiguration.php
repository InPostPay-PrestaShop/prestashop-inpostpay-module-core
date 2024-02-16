<?php

declare(strict_types=1);

namespace izi\prestashop\Configuration;

final class AdvancedConfiguration implements AdvancedConfigurationInterface, PersistentConfigurationInterface
{
    private const DEBUG_ENABLED = 'INPOST_PAY_DEBUG_ENABLED';

    /**
     * @var ConfigurationInterface
     */
    private $configuration;

    public function __construct(ConfigurationInterface $configuration)
    {
        $this->configuration = $configuration;
    }

    public function copy(): AdvancedConfigurationInterface
    {
        return new DTO\AdvancedConfiguration(
            $this->isDebugEnabled()
        );
    }

    public function persist(AdvancedConfigurationInterface $configuration): void
    {
        $this->configuration->set(self::DEBUG_ENABLED, $configuration->isDebugEnabled());
    }

    public function isDebugEnabled(): bool
    {
        return (bool) $this->configuration->get(self::DEBUG_ENABLED);
    }
}
