<?php

declare(strict_types=1);

use izi\prestashop\CommandBus;
use Symfony\Component\DependencyInjection\Argument\ServiceLocatorArgument;
use Symfony\Component\DependencyInjection\Argument\TaggedIteratorArgument;
use Symfony\Component\DependencyInjection\ContainerBuilder;

if (!defined('_PS_VERSION_') || Tools::version_compare(_PS_VERSION_, '8.0.0')) {
    return;
}

assert(isset($container) && $container instanceof ContainerBuilder);

$container->removeDefinition('inpost.izi.command_bus.handler_locator');
$container->getDefinition(CommandBus::class)
    ->setArgument(0, new ServiceLocatorArgument(new TaggedIteratorArgument(
        'inpost.izi.command_handler',
        'command_class',
        'getHandledCommandClass',
        true
    )));
