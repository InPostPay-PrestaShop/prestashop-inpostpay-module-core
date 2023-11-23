<?php

namespace izi\prestashop;

class BindingProvider
{
    private static $binding;

    public static function getBinding()
    {
        if (!self::$binding) {
            self::$binding = InpostIziPayPrestashop::getInstance()->getController()->basketBindingGet();
        }

        return self::$binding;
    }
}
