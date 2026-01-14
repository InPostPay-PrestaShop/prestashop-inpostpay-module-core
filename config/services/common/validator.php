<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Argument\ServiceLocatorArgument;
use Symfony\Component\DependencyInjection\Argument\TaggedIteratorArgument;
use Symfony\Component\DependencyInjection\ContainerBuilder;

if (!defined('_PS_VERSION_') || Tools::version_compare(_PS_VERSION_, '8.0.0')) {
    return;
}

assert(isset($container) && $container instanceof ContainerBuilder);

$container->removeDefinition('inpost.izi.validator.constraint_validator_locator');
$container
    ->getDefinition('inpost.izi.validator.constraint_validator_factory')
    ->setArgument(0, new ServiceLocatorArgument(new TaggedIteratorArgument(
        'validator.constraint_validator',
        null,
        null,
        true
    )));
