<?php

declare(strict_types=1);

namespace izi\prestashop\Handler\Config;

use izi\prestashop\Command\Config\UpdateShippingConfigurationCommand;
use izi\prestashop\Configuration\PersistentConfigurationInterface;
use izi\prestashop\Configuration\ShippingConfiguration;
use izi\prestashop\Configuration\ShippingConfigurationInterface;
use izi\prestashop\Handler\CommandHandlerTrait;

final class UpdateShippingConfigurationHandler implements UpdateShippingConfigurationHandlerInterface
{
    use CommandHandlerTrait;

    /**
     * @var PersistentConfigurationInterface<ShippingConfigurationInterface>
     */
    private $configuration;

    /**
     * @param ShippingConfiguration $configuration
     */
    public function __construct(ShippingConfigurationInterface $configuration)
    {
        $this->configuration = $configuration;
    }

    public function __invoke(UpdateShippingConfigurationCommand $command)
    {
        $this->configuration->persist($command->getConfiguration());
    }
}
