<?php

declare(strict_types=1);

namespace izi\prestashop\Uuid;

final class UuidV4 extends Uuid
{
    protected static function generate(): string
    {
        $uuid = random_bytes(16);
        $uuid[6] = $uuid[6] & "\x0F" | "\x40";
        $uuid[8] = $uuid[8] & "\x3F" | "\x80";
        $uuid = bin2hex($uuid);

        return implode('-', [
            substr($uuid, 0, 8),
            substr($uuid, 8, 4),
            substr($uuid, 12, 4),
            substr($uuid, 16, 4),
            substr($uuid, 20, 12),
        ]);
    }

    protected static function getVersion(): int
    {
        return 4;
    }
}
