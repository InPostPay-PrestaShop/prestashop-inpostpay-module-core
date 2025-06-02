<?php

declare(strict_types=1);

namespace izi\prestashop\Analytics\Cookie;

use Symfony\Component\HttpFoundation\Request;

final class GoogleClientIdCookie implements CookieExtractorInterface
{
    private const COOKIE_NAME = '_ga';

    public function extract(Request $request): ?string
    {
        if ($request->cookies->has(self::COOKIE_NAME)) {
            $gaCookie = $request->cookies->get(self::COOKIE_NAME);
            $parts = explode('.', $gaCookie);

            if (count($parts) >= 3) {
                return implode('.', array_slice($parts, 2));
            }
        }

        return null;
    }
}
