<?php

declare(strict_types=1);

namespace izi\prestashop\InPostDiscount\CartRule;

interface CustomRuleRepositoryInterface
{
    public function registerCartRule(string $discountType, int $cartRuleId): void;

    public function getCartRuleId(string $discountType): ?int;

    /**
     * @return int[]
     */
    public function getAllCustomRuleIds(): array;

    public function isCustomCartRule(int $cartRuleId): bool;
}
