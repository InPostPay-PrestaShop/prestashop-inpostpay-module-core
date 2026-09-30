<?php

declare(strict_types=1);

namespace izi\prestashop\InPostDiscount\CartRule;

use izi\prestashop\InPostDiscount\CartRuleDiscount;
use PrestaShop\PrestaShop\Core\Cart\CartRuleCalculator;
use PrestaShop\PrestaShop\Core\Cart\CartRuleData;
use Psr\Container\ContainerInterface;

final class DiscountApplier implements DiscountApplierInterface
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @param ContainerInterface $container locator of {@see DiscountApplierInterface} by discount type
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function apply(CartRuleCalculator $calculator, CartRuleData $data, CartRuleDiscount $discount, bool $withShipping): void
    {
        if (!$this->container->has($type = $discount->getType())) {
            return;
        }

        /** @var DiscountApplierInterface $applier */
        $applier = $this->container->get($type);
        $applier->apply($calculator, $data, $discount, $withShipping);
    }
}
