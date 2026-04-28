<?php

declare(strict_types=1);

namespace izi\prestashop\Analytics\Cookie\Factory;

use Symfony\Component\HttpFoundation\Cookie;

final class CookieFactory implements CookieFactoryInterface
{
    public function create($name, $value, int $expire = 0, string $path = '/', string $domain = '', bool $secure = false, bool $httpOnly = true): Cookie
    {
        if (!\is_string($name)) {
            throw new \InvalidArgumentException(\sprintf('Expected $name to be a string, "%s" given.', get_debug_type($name)));
        }

        if (null !== $value && !\is_string($value)) {
            throw new \InvalidArgumentException(\sprintf('Expected $value to be a string or null, "%s" given.', get_debug_type($value)));
        }

        return new Cookie($name, $value, $expire, $path, $domain, $secure, $httpOnly);
    }
}
