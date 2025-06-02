<?php

declare(strict_types=1);

namespace izi\prestashop\Analytics\Cookie\Repository;

use Symfony\Component\HttpFoundation\Cookie;

final class CookieRepository implements CookieRepositoryInterface
{
    public function persist(Cookie $cookie): void
    {
        setcookie(
            $cookie->getName(),
            $cookie->getValue(),
            $cookie->getExpiresTime(),
            $cookie->getPath(),
            $cookie->getDomain() ?? '',
            $cookie->isSecure(),
            $cookie->isHttpOnly()
        );
    }
}
