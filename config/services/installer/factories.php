<?php

declare(strict_types=1);

use Doctrine\ORM\EntityManagerInterface;
use PrestaShop\PrestaShop\Core\Localization\Locale\RepositoryInterface;
use PrestaShopBundle\Translation\Loader\DatabaseTranslationLoader;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Translation\TranslatorInterface as LegacyTranslatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

if (!defined('_PS_VERSION_')) {
    return;
}

assert(isset($container) && $container instanceof ContainerBuilder);

$services = [
    'doctrine.orm.entity_manager' => EntityManagerInterface::class,
    'prestashop.translation.database_loader' => DatabaseTranslationLoader::class,
    'prestashop.core.localization.locale.repository' => RepositoryInterface::class,
];

if (Tools::version_compare(_PS_VERSION_, '8.0.0')) {
    assert(interface_exists(LegacyTranslatorInterface::class));
    $services['translator'] = LegacyTranslatorInterface::class;
} else {
    $services['translator'] = TranslatorInterface::class;
}

$factory = [new Reference('app_container'), 'get'];

foreach ($services as $id => $class) {
    $container
        ->register($id, $class)
        ->setFactory($factory)
        ->setArguments([$id]);

    $container->setAlias($class, $id);
}
