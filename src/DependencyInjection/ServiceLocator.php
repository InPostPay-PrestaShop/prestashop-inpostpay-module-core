<?php

declare(strict_types=1);

namespace izi\prestashop\DependencyInjection;

use Symfony\Contracts\Service\ServiceLocatorTrait;
use Symfony\Contracts\Service\ServiceProviderInterface;

final class ServiceLocator implements ServiceProviderInterface
{
    use ServiceLocatorTrait;
}
