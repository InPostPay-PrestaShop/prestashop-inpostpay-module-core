<?php

declare(strict_types=1);

namespace izi\prestashop\Analytics\Cookie\Repository;

use Symfony\Component\HttpFoundation\Cookie;

interface CookieRepositoryInterface
{
    public function persist(Cookie $cookie): void;
}
