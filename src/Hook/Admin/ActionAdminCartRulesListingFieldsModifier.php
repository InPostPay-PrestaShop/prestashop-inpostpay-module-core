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
     * @param array{where: string} $parameters
     */
    public function execute(array $parameters): void
    {
        $where = $parameters['where'] ?? null;
        if (null !== $where && !\is_string($where)) {
            throw InvalidHookParamException::unexpectedType('where', $where, 'string|null');
        }

        $whereConditions = [];
        if (null !== $where) {
            $whereConditions[] = $where;
        }

        $qb = (new \DbQuery())
            ->select('cart_rule_id')
            ->from(CartRuleDiscountRepository::TABLE_NAME);

        $whereConditions[] = 'a.id_cart_rule NOT IN (' . $qb . ')';
        $parameters['where'] = implode(' AND ', $whereConditions);
    }
}
