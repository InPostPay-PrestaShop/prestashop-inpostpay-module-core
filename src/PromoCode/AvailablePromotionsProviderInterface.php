<?php

declare(strict_types=1);

namespace izi\prestashop\PromoCode;

use izi\prestashop\Common\Basket\AvailablePromotion;

interface AvailablePromotionsProviderInterface
{
    public const MAX_PROMO_COUNT = 5;

    /**
     * @return AvailablePromotion[]
     */
    public function getAvailablePromotions(\Cart $cart): array;
}
