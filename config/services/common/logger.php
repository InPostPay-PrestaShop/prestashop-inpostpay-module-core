<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\ContainerBuilder;

if (!defined('_PS_VERSION_') || Tools::version_compare(_PS_VERSION_, '8.0.0')) {
    return;
}

assert(isset($container) && $container instanceof ContainerBuilder);

if ($container->hasParameter('kernel.logs_dir')) {
    $container->setParameter('inpost.izi.logs_dir', '%kernel.logs_dir%/inpost');
} elseif ($container->hasParameter('kernel.project_dir')) {
    $container->setParameter('inpost.izi.logs_dir', '%kernel.project_dir%/var/logs/inpost');
}
