<?php

declare(strict_types=1);

namespace izi\prestashop\Analytics\Cookie;

use Symfony\Component\HttpFoundation\Request;

interface CookieExtractorInterface
{
    public function extract(Request $request): ?string;
}
