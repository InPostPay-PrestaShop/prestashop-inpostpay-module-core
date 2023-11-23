<?php

namespace izi;

class BasketIdentification
{
    const INPOSTIZI_BASKET_ID = 'inpostizi_basket_id';

    public static function get()
    {
        $identificationStored = Storage::findSession(self::INPOSTIZI_BASKET_ID);
        if ($identificationStored && InPostIzi::getCartSessionClass()::getRedirectedById($identificationStored) != 0) {
            Storage::eraseSession(self::INPOSTIZI_BASKET_ID);
        }
        $identificationStored = Storage::findSession(self::INPOSTIZI_BASKET_ID);
        if ($identificationStored) {
            return $identificationStored;
        }

        $identificationGenerated = IdentificationGenerator::generate();
        Storage::insertSession(self::INPOSTIZI_BASKET_ID, $identificationGenerated);

        return $identificationGenerated;
    }

    public static function drop()
    {
        InPostIzi::getLoggerClass()::log('DROPPING IDENTIFICATION');
        Storage::eraseSession(self::INPOSTIZI_BASKET_ID);
        Storage::eraseSession('binding_get');
    }
}
