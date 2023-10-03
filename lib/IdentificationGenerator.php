<?php

namespace izi;

class IdentificationGenerator
{
    public static function generate()
    {
        $id = implode('-', [
            IdentificationGenerator::random(8),
            IdentificationGenerator::random(4),
            IdentificationGenerator::random(4),
            IdentificationGenerator::random(4),
            IdentificationGenerator::random(12),
        ]);

        return $id;
    }

    public static function random($size)
    {
        return bin2hex(random_bytes($size / 2));
    }
}
