<?php

declare(strict_types=1);

namespace izi\prestashop\PromoCode;

final class CartRulePromoCode implements PromoCodeInterface
{
    /**
     * @var \CartRule
     */
    private $cartRule;

    public function __construct(\CartRule $cartRule)
    {
        $this->cartRule = $cartRule;
    }

    public function getCode(): string
    {
        $code = (string) $this->cartRule->code;

        return '' !== $code ? $code : (string) $this->cartRule->name;
    }

    public function getCartRule(): \CartRule
    {
        return $this->cartRule;
    }
}
