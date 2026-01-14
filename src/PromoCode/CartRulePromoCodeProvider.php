<?php

declare(strict_types=1);

namespace izi\prestashop\PromoCode;

use izi\prestashop\Common\PromoCode;

final class CartRulePromoCodeProvider implements PromoCodeProviderInterface
{
    /**
     * @var CartRuleOptionsRepositoryInterface
     */
    private $repository;

    public function __construct(CartRuleOptionsRepositoryInterface $cartRuleRepository)
    {
        $this->repository = $cartRuleRepository;
    }

    public function getPromoCodes(\Cart $cart): array
    {
        $cartRules = $cart->getCartRules(\CartRule::FILTER_ACTION_ALL, true, true);

        return array_map([$this, 'createPromoCode'], $cartRules);
    }

    private function createPromoCode(array $cartRule): PromoCode
    {
        $code = $cartRule['code'] ?: $cartRule['name'];
        $regulationType = $this->getRegulationType($cartRule);

        return new PromoCode($cartRule['name'], $code, $regulationType);
    }

    private function getRegulationType(array $cartRule): ?string
    {
        if ($this->repository->isOmnibus((int) $cartRule['id_cart_rule'])) {
            return PromoCode::REGULATION_TYPE_OMNIBUS;
        }

        return null;
    }
}
