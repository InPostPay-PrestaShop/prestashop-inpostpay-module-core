<?php

declare(strict_types=1);

namespace izi\prestashop\PromoCode;

final class NullAvailablePromotionsProvider implements AvailablePromotionsProviderInterface
{
    public function getAvailablePromotions(\Cart $cart): array
    {
        return [];
    }
}
