<?php

declare(strict_types=1);

use izi\prestashop\InPostDiscount\CartRule\DiscountApplier;
use izi\prestashop\InPostDiscount\CartRule\Factory\CartRuleFactory;
use Symfony\Component\DependencyInjection\Argument\ServiceLocatorArgument;
use Symfony\Component\DependencyInjection\Argument\TaggedIteratorArgument;
use Symfony\Component\DependencyInjection\ContainerBuilder;

if (!defined('_PS_VERSION_') || Tools::version_compare(_PS_VERSION_, '8.1.0')) {
    return;
}

assert(isset($container) && $container instanceof ContainerBuilder);

$container->removeDefinition('inpost.izi.inpost_discount.cart_rule_factory_locator');
$container->getDefinition(CartRuleFactory::class)
    ->setArgument(0, new ServiceLocatorArgument(new TaggedIteratorArgument(
        'inpost.izi.inpost_discount.cart_rule_factory',
        'discount_type',
        null,
        true
    )));

$container->removeDefinition('inpost.izi.inpost_discount.applier_locator');
$container->getDefinition(DiscountApplier::class)
    ->setArgument(0, new ServiceLocatorArgument(new TaggedIteratorArgument(
        'inpost.izi.inpost_discount.applier',
        'discount_type',
        null,
        true
    )));
