<?php

namespace izi;

class PriceNumber
{
    public static function parse($float, $size = 6)
    {
        $explode = explode(".", strval($float));
        $number = intval(implode($explode));
        if(count($explode) === 2) {
            return $number * pow(10, $size - strlen($explode[1]));
        }

        return intval($float * pow(10, $size));
    }

    public static function toFloat($number, $size = 6) {
        return $number / pow(10, $size);
    }
}

