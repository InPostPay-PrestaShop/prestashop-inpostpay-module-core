<?php

declare(strict_types=1);

namespace izi\prestashop\Hook\Admin;

use izi\prestashop\Hook\Exception\InvalidHookParamException;
use izi\prestashop\Hook\HookInterface;
use izi\prestashop\InPostDiscount\CartRuleDiscountRepository;

final class ActionAdminCartRulesListingFieldsModifier implements HookInterface
{
    public const HOOK_NAME = 'actionAdminCartRulesListingFieldsModifier';

    public static function getHookName(): string
    {
        return self::HOOK_NAME;
    }

    /**
     * @param array{where: string|null} $parameters
     */
    public function execute(array $parameters): void
    {
        if (!\is_string($where = $parameters['where'] ?? '')) {
            throw InvalidHookParamException::unexpectedType('where', $where, 'string|null');
        }

        $qb = (new \DbQuery())
            ->select('cart_rule_id')
            ->from(CartRuleDiscountRepository::TABLE_NAME);

        $where .= ' AND a.id_cart_rule NOT IN (' . $qb . ')';
        $parameters['where'] = $where;
    }
}
