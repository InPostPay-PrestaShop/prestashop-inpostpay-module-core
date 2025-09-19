<?php

declare(strict_types=1);

namespace izi\prestashop\PromoCode;

use izi\prestashop\PromoCode\Exception\CouldNotAddPromoCodeException;
use izi\prestashop\PromoCode\Exception\CouldNotRemovePromoCodeException;
use izi\prestashop\PromoCode\Exception\InvalidPromoCodeException;

interface PromoCodeManagerInterface
{
    /**
     * @return PromoCodeInterface[]
     */
    public function getPromoCodes(\Cart $cart): array;

    /**
     * @param non-empty-string $code
     *
     * @throws InvalidPromoCodeException
     * @throws CouldNotAddPromoCodeException
     */
    public function addPromoCode(\Cart $cart, string $code);

    /**
     * @throws CouldNotRemovePromoCodeException
     */
    public function removePromoCode(\Cart $cart, PromoCodeInterface $promoCode);
}
