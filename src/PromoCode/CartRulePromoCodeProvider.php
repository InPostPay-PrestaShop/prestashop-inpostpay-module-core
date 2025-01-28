<?php

declare(strict_types=1);

namespace izi\prestashop\PromoCode;

use izi\prestashop\Common\PromoCode;
use izi\prestashop\Configuration\Adapter\Configuration;
use izi\prestashop\Database\Connection;
use izi\prestashop\Repository\CartRuleRepository;
use izi\prestashop\Repository\CartRuleRepositoryInterface;

class CartRulePromoCodeProvider implements PromoCodeProviderInterface
{
    /**
     * @var CartRuleRepositoryInterface
     */
    private $cartRuleRepository;

    public function __construct(CartRuleRepositoryInterface $cartRuleRepository)
    {
        $this->cartRuleRepository = $cartRuleRepository;
    }

    /**
     * @internal
     */
    public static function create(): self
    {
        $db = \Db::getInstance();

        return new self(new CartRuleRepository(new Connection($db), new Configuration($db)));
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
        if ($this->cartRuleRepository->isOmnibus((int) $cartRule['id_cart_rule'])) {
            return PromoCode::REGULATION_TYPE_OMNIBUS;
        }

        return null;
    }
}
