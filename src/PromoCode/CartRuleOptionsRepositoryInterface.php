<?php

declare(strict_types=1);

namespace izi\prestashop\PromoCode;

interface CartRuleOptionsRepositoryInterface
{
    public function add(CartRuleOptions $options): void;

    public function find(int $cartRuleId): ?CartRuleOptions;

    public function update(CartRuleOptions $options): void;

    /**
     * @return bool whether cart rule falls under the Omnibus Directive
     */
    public function isOmnibus(int $cartRuleId): bool;
}
