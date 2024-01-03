<?php

declare(strict_types=1);

namespace izi\prestashop\ObjectModel\Repository;

use izi\prestashop\ObjectModel\ObjectManagerInterface;
use Psr\Container\ContainerInterface;

final class ObjectRepositoryFactory implements ObjectRepositoryFactoryInterface
{
    /**
     * @var ContainerInterface
     */
    private $locator;

    private $repositories = [];

    public function __construct(ContainerInterface $locator)
    {
        $this->locator = $locator;
    }

    public function getRepository(ObjectManagerInterface $manager, string $class): ObjectRepositoryInterface
    {
        if ($this->locator->has($class)) {
            return $this->locator->get($class);
        }

        return $this->repositories[$class] ?? ($this->repositories[$class] = $this->createRepository($manager, $class));
    }

    private function createRepository(ObjectManagerInterface $manager, string $class): ObjectRepositoryInterface
    {
        return new ObjectRepository($class, $manager);
    }
}
