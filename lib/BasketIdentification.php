<?php

namespace izi;

class BasketIdentification
{
    public const INPOSTIZI_BASKET_ID = 'inpostizi_basket_id';

    public static function store(string $basketId): void
    {
        Storage::insertSession(self::INPOSTIZI_BASKET_ID, $basketId);
        Storage::eraseSession('binding_get');
    }

    public static function exists(): bool
    {
        return Storage::issetSession(self::INPOSTIZI_BASKET_ID);
    }

    public static function get(): string
    {
        $basketId = Storage::findSession(self::INPOSTIZI_BASKET_ID);

        if (null === $basketId && $currentBasketId = InPostIzi::getCartSessionClass()::getCurrentBasketId()) {
            self::store($currentBasketId);

            return $currentBasketId;
        }

        if (is_string($basketId) && !InPostIzi::getCartSessionClass()::getRedirectedById($basketId)) {
            return $basketId;
        }

        $newBasketId = IdentificationGenerator::generate();
        self::store($newBasketId);

        return $newBasketId;
    }

    public static function drop()
    {
        InPostIzi::getLoggerClass()::log('DROPPING IDENTIFICATION');
        Storage::eraseSession(self::INPOSTIZI_BASKET_ID);
        Storage::eraseSession('binding_get');
    }
}
