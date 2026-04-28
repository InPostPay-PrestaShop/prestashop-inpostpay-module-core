<?php

declare(strict_types=1);

namespace izi\prestashop\Analytics\Cookie;

use izi\prestashop\Analytics\Cookie\Factory\CookieFactoryInterface;
use izi\prestashop\Analytics\Cookie\Repository\CookieRepositoryInterface;
use izi\prestashop\Analytics\Parameters;

final class GoogleClickIdCookie extends GenericClickIdExtractor
{
    public function __construct(CookieFactoryInterface $cookieFactory, CookieRepositoryInterface $cookieRepository, array $options = [])
    {
        parent::__construct($cookieFactory, $cookieRepository, Parameters::GOOGLE_CLICK_ID, $options);
    }
}
