<?php

declare(strict_types=1);

namespace izi\prestashop\Command\Config;

use izi\prestashop\Configuration\AdvancedConfigurationInterface;
use izi\prestashop\Handler\Config\UpdateAdvancedConfigurationHandler;

/**
 * @see UpdateAdvancedConfigurationHandler
 */
final class UpdateAdvancedConfigurationCommand
{
    /**
     * @var AdvancedConfigurationInterface
     */
    private $configuration;

    public function __construct(AdvancedConfigurationInterface $configuration)
    {
        $this->configuration = $configuration;
    }

    public function getConfiguration(): AdvancedConfigurationInterface
    {
        return $this->configuration;
    }
}
