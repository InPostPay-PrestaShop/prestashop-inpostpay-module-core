<?php

declare(strict_types=1);

namespace izi\prestashop\Command\Config;

use izi\prestashop\Configuration\DTO\GeneralConfiguration;
use izi\prestashop\Handler\Config\UpdateGeneralConfigurationHandler;

/**
 * @see UpdateGeneralConfigurationHandler
 */
final class UpdateGeneralConfigurationCommand
{
    /**
     * @var GeneralConfiguration
     */
    private $configuration;

    public function __construct(GeneralConfiguration $configuration)
    {
        $this->configuration = $configuration;
    }

    public function getConfiguration(): GeneralConfiguration
    {
        return $this->configuration;
    }
}
