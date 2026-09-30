<?php

declare(strict_types=1);

namespace izi\prestashop\InPostDiscount\CartRule\Factory;

use izi\prestashop\InPostDiscount\CartRuleDiscount;
use izi\prestashop\InPostDiscount\Exception\UnsupportedTypeException;
use izi\prestashop\MerchantApi\Model\Order\Request\InPostDiscount;
use Psr\Container\ContainerInterface;

final class CartRuleFactory implements CartRuleFactoryInterface
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @param ContainerInterface $container locator of {@see CartRuleFactoryInterface} by discount type
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function create(\Cart $cart, InPostDiscount $discount): CartRuleDiscount
    {
        if (!$this->container->has($type = $discount->getType())) {
            throw UnsupportedTypeException::create($type);
        }

        /** @var CartRuleFactoryInterface $factory */
        $factory = $this->container->get($type);

        return $factory->create($cart, $discount);
    }
}
