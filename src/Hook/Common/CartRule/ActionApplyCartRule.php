<?php

declare(strict_types=1);

namespace izi\prestashop\Hook\Common\CartRule;

use izi\prestashop\Hook\Exception\InvalidHookParamException;
use izi\prestashop\Hook\PrestaShopVersionAwareHookInterface;
use izi\prestashop\Hook\VersionRange;
use izi\prestashop\InPostDiscount\CartRule\DiscountApplierInterface;
use izi\prestashop\InPostDiscount\CartRuleDiscountRepository;
use izi\prestashop\InPostDiscount\DiscountRepositoryInterface;
use PrestaShop\PrestaShop\Core\Cart\CartRuleCalculator;
use PrestaShop\PrestaShop\Core\Cart\CartRuleData;

final class ActionApplyCartRule implements PrestaShopVersionAwareHookInterface
{
    public const HOOK_NAME = 'actionApplyCartRule';

    /**
     * @var CartRuleDiscountRepository
     */
    private $repository;

    /**
     * @var DiscountApplierInterface
     */
    private $discountApplier;

    /**
     * @param CartRuleDiscountRepository $repository
     */
    public function __construct(DiscountRepositoryInterface $repository, DiscountApplierInterface $discountApplier)
    {
        $this->repository = $repository;
        $this->discountApplier = $discountApplier;
    }

    public static function getHookName(): string
    {
        return self::HOOK_NAME;
    }

    public static function getVersionRange(): VersionRange
    {
        return new VersionRange('9.0.1');
    }

    /**
     * @param array{cart_rule_calculator: CartRuleCalculator, cart_rule_data: CartRuleData, cart: \Cart, with_free_shipping: bool, is_applied_by_modules: bool|null} $parameters
     */
    public function execute(array $parameters): void
    {
        [$calculator, $data, $cart, $withShipping, $applied] = $this->extractParameters($parameters);

        if ($applied) {
            return; // already handled by another module
        }

        if (0 >= $cartId = (int) $cart->id) {
            return;
        }

        $cartRuleId = (int) $data->getCartRule()->id;

        if (null === $discount = $this->repository->findOneByCartAndRuleId($cartId, $cartRuleId)) {
            return;
        }

        $this->discountApplier->apply($calculator, $data, $discount, $withShipping);
        $parameters['is_applied_by_modules'] = true;
    }

    /**
     * @param array{cart_rule_calculator: CartRuleCalculator, cart_rule_data: CartRuleData, cart: \Cart, with_free_shipping: bool, is_applied_by_modules: bool} $parameters
     *
     * @return array{0: CartRuleCalculator, 1: CartRuleData, 2: \Cart, 3: bool, 4: bool|null}
     */
    private function extractParameters(array $parameters): array
    {
        $calculator = $parameters['cart_rule_calculator'] ?? null;
        if (!$calculator instanceof CartRuleCalculator) {
            throw InvalidHookParamException::unexpectedType('cart_rule_calculator', $calculator, CartRuleCalculator::class);
        }

        $data = $parameters['cart_rule_data'] ?? null;
        if (!$data instanceof CartRuleData) {
            throw InvalidHookParamException::unexpectedType('cart_rule_data', $data, CartRuleData::class);
        }

        $cart = $parameters['cart'] ?? null;
        if (!$cart instanceof \Cart) {
            throw InvalidHookParamException::unexpectedType('cart', $cart, \Cart::class);
        }

        if (!\is_bool($withShipping = $parameters['with_free_shipping'] ?? null)) {
            throw InvalidHookParamException::unexpectedType('with_free_shipping', $withShipping, 'bool');
        }

        return [$calculator, $data, $cart, $withShipping, $parameters['is_applied_by_modules'] ?? null];
    }
}
