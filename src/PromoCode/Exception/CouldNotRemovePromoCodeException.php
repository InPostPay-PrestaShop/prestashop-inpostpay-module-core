<?php

declare(strict_types=1);

namespace izi\prestashop\PromoCode\Exception;

class CouldNotRemovePromoCodeException extends \RuntimeException implements PromoCodeExceptionInterface
{
    public static function create(?\Throwable $previous = null): self
    {
        return new self('Promo code could not be removed from cart.', 0, $previous);
    }
}
