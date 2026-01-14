<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\ContainerBuilder;

if (!defined('_PS_VERSION_') || Tools::version_compare(_PS_VERSION_, '8.0.0')) {
    return;
}

assert(isset($container) && $container instanceof ContainerBuilder);

$container->setAlias('inpost.izi.translator', 'inpost.izi.context_translator');
