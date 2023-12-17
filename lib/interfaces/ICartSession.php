<?php

namespace izi\interfaces;

interface ICartSession
{
    public static function storeCurrent(): void;

    public static function setConfirmationToCart(string $basketId, $confirmation): void;

    public static function getCartOrderRedirectUrl(string $basketId): ?string;

    public static function getCartConfirmation(string $basketId): ?string;

    public static function getCurrentBasketId(): ?string;
}
