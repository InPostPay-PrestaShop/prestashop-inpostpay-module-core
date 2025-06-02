<?php

declare(strict_types=1);

namespace izi\prestashop\Analytics\Cookie\Factory;

use Symfony\Component\HttpFoundation\Cookie;

interface CookieFactoryInterface
{
    public function create(
        $name,
        $value,
        int $expire = 0,
        string $path = '/',
        string $domain = '',
        bool $secure = false,
        bool $httpOnly = true
    ): Cookie;
}
