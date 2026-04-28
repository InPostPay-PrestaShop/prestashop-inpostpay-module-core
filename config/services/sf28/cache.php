<?php

declare(strict_types=1);

use izi\prestashop\Extension\CachedExtensionsService;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\DependencyInjection\ContainerBuilder;

if (!defined('_PS_VERSION_') || class_exists(FilesystemAdapter::class)) {
    return;
}

assert(isset($container) && $container instanceof ContainerBuilder);

$container->removeDefinition('inpost.izi.cache');
$container->removeDefinition(CachedExtensionsService::class);
