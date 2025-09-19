<?php

declare(strict_types=1);

namespace izi\prestashop\PromoCode;

interface PromoCodeInterface
{
    public function getCode(): string;
}
