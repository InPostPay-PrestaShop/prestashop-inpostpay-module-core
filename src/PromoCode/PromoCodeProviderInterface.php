<?php

declare(strict_types=1);

namespace izi\prestashop\PromoCode;

use izi\prestashop\Common\PromoCode;

interface PromoCodeProviderInterface
{
    /**
     * @param \Cart $cart
     *
     * @return PromoCode[]
     */
    public function getPromoCodes(\Cart $cart): array;
}
