<?php

declare(strict_types=1);

use izi\prestashop\Form\TypeExtension\DatePickerCompatibilityTypeExtension;
use izi\prestashop\Form\TypeExtension\DateTimeImmutableTimeTypeExtension;
use izi\prestashop\Form\TypeExtension\UnitTypeExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;

if (!defined('_PS_VERSION_') || Tools::version_compare(_PS_VERSION_, '8.0.0')) {
    return;
}

assert(isset($container) && $container instanceof ContainerBuilder);

$container->removeDefinition(DatePickerCompatibilityTypeExtension::class);
$container->removeDefinition(DateTimeImmutableTimeTypeExtension::class);
$container->removeDefinition(UnitTypeExtension::class);
