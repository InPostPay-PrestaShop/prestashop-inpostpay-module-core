<?php

declare(strict_types=1);

namespace izi\prestashop\Product\Util;

/**
 * @interal
 */
final class DescriptionFormatter
{
    public static function formatDescription(\Product $product): string
    {
        if ('' !== $description = self::doFormat((string) $product->description)) {
            return $description;
        }

        return self::doFormat((string) $product->description_short);
    }

    private static function doFormat(string $description): string
    {
        $description = strip_tags($description);
        $description = trim(preg_replace('/\s+/', ' ', $description));

        if ('' === $description) {
            return '';
        }

        $description = htmlentities($description, \ENT_HTML401, 'utf-8', false);
        $description = htmlspecialchars_decode($description);
        $description = preg_replace('/&(?:#\d+|[a-zA-Z]+);/', '', $description);

        return \Tools::substr($description, 0, 1000);
    }
}
