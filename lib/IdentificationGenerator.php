<?php

namespace izi;

class IdentificationGenerator
{
    public static function generate(): string
    {
        return implode('-', [
            self::random(8),
            self::random(4),
            self::random(4),
            self::random(4),
            self::random(12),
        ]);
    }

    public static function random($size): string
    {
        return bin2hex(random_bytes($size / 2));
    }
}
