<?php

declare(strict_types=1);

namespace izi\prestashop\Handler\Config;

use izi\prestashop\Command\Config\UpdateConsentsConfigurationCommand;
use izi\prestashop\Configuration\ConsentsConfiguration;
use izi\prestashop\Configuration\ConsentsConfigurationInterface;
use izi\prestashop\Configuration\PersistentConfigurationInterface;
use izi\prestashop\Handler\CommandHandlerTrait;
use Psr\Clock\ClockInterface;

final class UpdateConsentsConfigurationHandler implements UpdateConsentsConfigurationHandlerInterface
{
    use CommandHandlerTrait;

    /**
     * @var PersistentConfigurationInterface<ConsentsConfigurationInterface>
     */
    private $configuration;

    /**
     * @var ClockInterface
     */
    private $clock;

    /**
     * @param ConsentsConfiguration $configuration
     */
    public function __construct(ConsentsConfigurationInterface $configuration, ClockInterface $clock)
    {
        $this->configuration = $configuration;
        $this->clock = $clock;
    }

    public function __invoke(UpdateConsentsConfigurationCommand $command)
    {
        $now = $this->clock->now();

        foreach ($command->getConsents() as $consent) {
            $consent->setDateUpdated($now);
        }

        $this->configuration->persist($command);
    }
}
