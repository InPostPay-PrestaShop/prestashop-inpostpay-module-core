<?php

declare(strict_types=1);

namespace izi\prestashop\Handler\Config;

use izi\prestashop\Command\Config\UpdateGuiConfigurationCommand;
use izi\prestashop\Configuration\GuiConfiguration;
use izi\prestashop\Configuration\GuiConfigurationInterface;
use izi\prestashop\Configuration\PersistentConfigurationInterface;
use izi\prestashop\Handler\CommandHandlerTrait;

final class UpdateGuiConfigurationHandler implements UpdateGuiConfigurationHandlerInterface
{
    use CommandHandlerTrait;

    /**
     * @var PersistentConfigurationInterface<GuiConfigurationInterface>
     */
    private $configuration;

    /**
     * @param GuiConfiguration $configuration
     */
    public function __construct(GuiConfigurationInterface $configuration)
    {
        $this->configuration = $configuration;
    }

    public function __invoke(UpdateGuiConfigurationCommand $command)
    {
        $this->configuration->persist($command->getConfiguration());
    }
}
