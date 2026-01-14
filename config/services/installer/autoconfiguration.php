<?php

declare(strict_types=1);

use izi\prestashop\Installer\Database\MigrationInterface;
use izi\prestashop\Installer\InstallerInterface;
use izi\prestashop\Installer\UninstallerInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

if (!defined('_PS_VERSION_')) {
    return;
}

assert(isset($container) && $container instanceof ContainerBuilder);

$container
    ->registerForAutoconfiguration(InstallerInterface::class)
    ->addTag('inpost.izi.installer');
$container
    ->registerForAutoconfiguration(UninstallerInterface::class)
    ->addTag('inpost.izi.uninstaller');
$container
    ->registerForAutoconfiguration(MigrationInterface::class)
    ->addTag('inpost.izi.db_migration');
