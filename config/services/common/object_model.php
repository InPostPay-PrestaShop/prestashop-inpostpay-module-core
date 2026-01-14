<?php

declare(strict_types=1);

use izi\prestashop\ObjectModel\Repository\ObjectRepositoryFactory;
use Symfony\Component\DependencyInjection\Argument\ServiceLocatorArgument;
use Symfony\Component\DependencyInjection\Argument\TaggedIteratorArgument;
use Symfony\Component\DependencyInjection\ContainerBuilder;

if (!defined('_PS_VERSION_') || Tools::version_compare(_PS_VERSION_, '8.0.0')) {
    return;
}

assert(isset($container) && $container instanceof ContainerBuilder);

$container->removeDefinition('inpost.izi.object_model.repository_locator');
$container->getDefinition(ObjectRepositoryFactory::class)
    ->setArgument(0, new ServiceLocatorArgument(new TaggedIteratorArgument(
        'inpost.izi.model_repository',
        'model_class',
        null,
        true
    )));

// TODO: autoconfigure tags on PS 8.1 or later
