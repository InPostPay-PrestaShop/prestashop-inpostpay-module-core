<?php

declare(strict_types=1);

namespace izi\prestashop;

use Psr\Container\ContainerInterface;

final class CommandBus implements CommandBusInterface
{
    /**
     * @var ContainerInterface
     */
    private $locator;

    public function __construct(ContainerInterface $locator)
    {
        $this->locator = $locator;
    }

    /**
     * {@inheritDoc}
     */
    public function handle(object $command)
    {
        $class = \get_class($command);
        $handler = $this->locator->get($class);

        if (!\is_callable($handler)) {
            throw new \LogicException(\sprintf('Handler for command "%s" is not callable', $class));
        }

        return $handler($command);
    }
}
