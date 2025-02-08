<?php

declare(strict_types=1);

namespace izi\prestashop\Environment;

interface EnvironmentFactoryInterface
{
    public function createEnvironment(EnvironmentType $type): EnvironmentInterface;
}
