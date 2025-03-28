<?php

declare(strict_types=1);

use izi\prestashop\Form\TypeExtension\DatePickerCompatibilityTypeExtension;
use izi\prestashop\Form\TypeExtension\DateTimeImmutableTimeTypeExtension;
use izi\prestashop\Form\TypeExtension\HelpTextExtension;
use izi\prestashop\Form\TypeExtension\UnitTypeExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;

if (!defined('_PS_VERSION_') || Tools::version_compare(_PS_VERSION_, '8.0.0')) {
    return;
}

assert(isset($container) && $container instanceof ContainerBuilder);

$classes = [
    DateTimeImmutableTimeTypeExtension::class,
    HelpTextExtension::class,
    UnitTypeExtension::class,
];

$container->removeDefinition(DatePickerCompatibilityTypeExtension::class);

foreach ($classes as $class) {
    $container
        ->getDefinition($class)
        ->setTags([])
        ->setDeprecated();
}
