<?php

declare(strict_types=1);

namespace izi\prestashop\Hook\Common\CartRule;

use izi\prestashop\Hook\Exception\InvalidHookParamException;
use izi\prestashop\Hook\PrestaShopVersionAwareHookInterface;
use izi\prestashop\Hook\VersionRange;
use izi\prestashop\InPostDiscount\CartRuleDiscountRepository;
use izi\prestashop\InPostDiscount\DiscountRepositoryInterface;

final class ActionGetCartRuleContextualValue implements PrestaShopVersionAwareHookInterface
{
    public const HOOK_NAME = 'actionGetCartRuleContextualValue';

    /**
     * @var \Context
     */
    private $context;

    /**
     * @var CartRuleDiscountRepository
     */
    private $repository;

    /**
     * @param CartRuleDiscountRepository $repository
     */
    public function __construct(\Context $context, DiscountRepositoryInterface $repository)
    {
        $this->context = $context;
        $this->repository = $repository;
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
     * @param array{cart_rule: \CartRule, use_tax: bool, context: \Context|null, filter: int, contextualValueFromModules: float|null} $parameters
     */
    public function execute(array $parameters): void
    {
        [$cartRule, $withTax, $context , $filter, $value] = $this->extractParameters($parameters);

        if (null !== $value) {
            return; // already handled by another module
        }

        if (!\in_array($filter, [\CartRule::FILTER_ACTION_ALL, \CartRule::FILTER_ACTION_REDUCTION, \CartRule::FILTER_ACTION_ALL_NOCAP], true)) {
            return;
        }

        if (0 >= $cartId = (int) $context->cart->id) {
            return;
        }

        $cartRuleId = (int) $cartRule->id;

        if (null === $discount = $this->repository->findOneByCartAndRuleId($cartId, $cartRuleId)) {
            return;
        }

        $parameters['contextualValueFromModules'] = $withTax
            ? $discount->getAmount()->getGross()
            : $discount->getAmount()->getNet();
    }

    /**
     * @param array{cart_rule: \CartRule, use_tax: bool, context: \Context|null, filter: int, contextualValueFromModules: float|null} $parameters
     *
     * @return array{0: \CartRule, 1: bool, 2: \Context, 3: int, 4: float|null}
     */
    private function extractParameters(array $parameters): array
    {
        $cartRule = $parameters['cart_rule'] ?? null;
        if (!$cartRule instanceof \CartRule) {
            throw InvalidHookParamException::unexpectedType('cart_rule', $cartRule, \CartRule::class);
        }

        if (!\is_bool($withTax = $parameters['use_tax'] ?? null)) {
            throw InvalidHookParamException::unexpectedType('use_tax', $withTax, 'bool');
        }

        $context = $parameters['context'] ?? null;
        if (null !== $context && !$context instanceof \Context) {
            throw InvalidHookParamException::unexpectedType('context', $context, \Context::class . '|null');
        }

        $filter = $parameters['filter'] ?? null;
        if (null !== $filter && !\is_int($filter)) {
            throw InvalidHookParamException::unexpectedType('filter', $filter, 'int|null');
        }

        return [$cartRule, $withTax, $context ?? $this->context, $filter ?? \CartRule::FILTER_ACTION_ALL, $parameters['contextualValueFromModules'] ?? null];
    }
}
