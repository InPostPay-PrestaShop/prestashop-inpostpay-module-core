<?php

declare(strict_types=1);

namespace izi\prestashop\InPostDiscount;

use izi\prestashop\InPostDiscount\CartRule\Factory\CartRuleFactoryInterface;
use izi\prestashop\InPostDiscount\Exception\ZeroAmountException;
use izi\prestashop\MerchantApi\Model\Order\Request\InPostDiscount;
use izi\prestashop\ObjectModel\ObjectManagerInterface;

/**
 * @template-implements DiscountHandlerInterface<CartRuleDiscount>
 */
final class CartRuleDiscountHandler implements DiscountHandlerInterface
{
    /**
     * @var \Context
     */
    private $context;

    /**
     * @var CartRuleFactoryInterface
     */
    private $factory;

    /**
     * @var CartRuleDiscountRepository
     */
    private $repository;

    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @param CartRuleDiscountRepository $repository
     */
    public function __construct(\Context $context, CartRuleFactoryInterface $factory, DiscountRepositoryInterface $repository, ObjectManagerInterface $objectManager)
    {
        $this->context = $context;
        $this->factory = $factory;
        $this->repository = $repository;
        $this->objectManager = $objectManager;
    }

    public function apply(\Cart $cart, InPostDiscount $discount): ?DiscountInterface
    {
        try {
            $cartRuleDiscount = $this->factory->create($cart, $discount);
        } catch (ZeroAmountException $e) {
            return null;
        }

        try {
            $this->doApply($cart, $cartRuleDiscount);

            return $cartRuleDiscount;
        } catch (\Throwable $e) {
            try {
                $this->repository->remove($cartRuleDiscount);
                $this->removeIfSingleUse($cartRuleDiscount->getCartRuleId());
            } catch (\Throwable $ignored) {
                // ignore and rethrow the original exception
            }

            throw $e;
        }
    }

    public function remove(\Cart $cart, DiscountInterface $discount): void
    {
        if (!$discount instanceof CartRuleDiscount) {
            throw new \InvalidArgumentException(\sprintf('Expected $discount to be an instance of "%s", "%s" given', CartRuleDiscount::class, \get_class($discount)));
        }

        if (!$cart->removeCartRule($cartRuleId = $discount->getCartRuleId())) {
            throw new \RuntimeException(\sprintf('Failed to remove cart rule #%d from the cart.', $cartRuleId));
        }

        $this->repository->remove($discount);
        $this->removeIfSingleUse($cartRuleId);
    }

    private function doApply(\Cart $cart, CartRuleDiscount $discount): void
    {
        if (null === $cartRule = $this->objectManager->find(\CartRule::class, $cartRuleId = $discount->getCartRuleId())) {
            throw new \RuntimeException(\sprintf('Cart rule #%d does not exist.', $cartRuleId));
        }

        $this->repository->add($discount);

        $context = $this->context->cloneContext();
        $context->cart = $cart;

        if (null !== $error = $cartRule->checkValidity($context)) {
            throw new \RuntimeException(\sprintf('Discount could not be added to cart: %s', $error));
        }

        if (true === $result = $cart->addCartRule($cartRuleId)) {
            return;
        }

        if (\is_string($result)) {
            throw new \RuntimeException(\sprintf('Discount could not be added to cart: %s', $result));
        }

        throw new \RuntimeException('Failed to add discount to cart.');
    }

    private function removeIfSingleUse(int $cartRuleId): void
    {
        if (null === $cartRule = $this->objectManager->find(\CartRule::class, $cartRuleId)) {
            return;
        }

        if (0 >= $cartRule->id_customer) {
            return;
        }

        $this->objectManager->remove($cartRule);
    }
}
