<?php

declare(strict_types=1);

namespace izi\prestashop\PromoCode;

use izi\prestashop\Common\PromoCode;
use izi\prestashop\Repository\CartRuleRepositoryInterface;

/**
 * @final
 */
class CartRulePromoCodeProvider implements PromoCodeProviderInterface
{
    /**
     * @var CartRuleOptionsRepositoryInterface|CartRuleRepositoryInterface
     */
    private $repository;

    /**
     * @param CartRuleOptionsRepositoryInterface $cartRuleRepository
     */
    public function __construct($cartRuleRepository)
    {
        if (!$cartRuleRepository instanceof CartRuleOptionsRepositoryInterface) {
            if (!$cartRuleRepository instanceof CartRuleRepositoryInterface) {
                throw new \InvalidArgumentException(sprintf('Expected an instance of "%s" or "%s", "%s" given.', CartRuleOptionsRepositoryInterface::class, CartRuleRepositoryInterface::class, get_debug_type($cartRuleRepository)));
            }

            @trigger_error(sprintf('"%s" is deprecated since 2.1.0, use "%s" instead.', CartRuleRepositoryInterface::class, CartRuleOptionsRepositoryInterface::class), E_USER_DEPRECATED);
        }

        $this->repository = $cartRuleRepository;
    }

    /**
     * @internal
     */
    public static function create(): self
    {
        $repository = CartRuleOptionsRepository::create();

        return new self($repository);
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
