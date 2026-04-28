<?php

declare(strict_types=1);

namespace izi\prestashop\Analytics\Cookie\Repository;

use Symfony\Component\HttpFoundation\Cookie;

final class CookieRepository implements CookieRepositoryInterface
{
    public function persist(Cookie $cookie): void
    {
        $name = $cookie->getName();

        if (null === $value = $cookie->getValue()) {
            unset($_COOKIE[$name]);
        }

        setcookie(
            $name,
            $value ?? '',
            $cookie->getExpiresTime(),
            $cookie->getPath(),
            $cookie->getDomain() ?? '',
            $cookie->isSecure(),
            $cookie->isHttpOnly()
        );
    }
}
