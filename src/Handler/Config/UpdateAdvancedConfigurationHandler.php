<?php

declare(strict_types=1);

namespace izi\prestashop\Handler\Config;

use izi\prestashop\Command\Config\UpdateAdvancedConfigurationCommand;
use izi\prestashop\Configuration\AdvancedConfiguration;
use izi\prestashop\Configuration\AdvancedConfigurationInterface;
use izi\prestashop\Handler\CommandHandlerTrait;

final class UpdateAdvancedConfigurationHandler implements UpdateAdvancedConfigurationHandlerInterface
{
    use CommandHandlerTrait;

    /**
     * @var AdvancedConfigurationInterface
     */
    private $configuration;

    /**
     * @param AdvancedConfiguration $configuration
     */
    public function __construct(AdvancedConfiguration $configuration)
    {
        $this->configuration = $configuration;
    }

    public function __invoke(UpdateAdvancedConfigurationCommand $command)
    {
        $this->configuration->persist($command->getConfiguration());
    }
}
