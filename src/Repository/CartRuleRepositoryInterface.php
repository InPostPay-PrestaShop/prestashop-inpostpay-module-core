<?php

declare(strict_types=1);

namespace izi\prestashop\Repository;

interface CartRuleRepositoryInterface
{
    /**
     * @return bool whether cart rule falls under the Omnibus Directive
     */
    public function isOmnibus(int $cartRuleId): bool;

    public function setOmnibus(int $cartRuleId, bool $isOmnibus): void;
}
