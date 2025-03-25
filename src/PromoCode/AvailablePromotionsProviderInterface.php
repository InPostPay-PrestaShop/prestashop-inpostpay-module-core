<?php

declare(strict_types=1);

namespace izi\prestashop\PromoCode;

use izi\prestashop\Common\Basket\AvailablePromotion;

interface AvailablePromotionsProviderInterface
{
    /**
     * @return AvailablePromotion[]
     */
    public function getAvailablePromotions(\Cart $cart): array;
}
