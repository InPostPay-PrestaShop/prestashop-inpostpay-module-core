<?php

namespace izi\interfaces;

interface ICartSession
{
    public static function storeCurrent(): void;

    public static function setConfirmationToCart($cartId, $confirmation): void;

    public static function getCartOrderRedirectUrl($cartId): ?string;

    public static function getCartConfirmation($cartId): ?string;
}
