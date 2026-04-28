<?php

declare(strict_types=1);

namespace izi\prestashop\Analytics\Cookie;

use izi\prestashop\Analytics\Cookie\Factory\CookieFactoryInterface;
use izi\prestashop\Analytics\Cookie\Repository\CookieRepositoryInterface;
use izi\prestashop\Analytics\Parameters;
use Symfony\Component\HttpFoundation\Request;

final class TikTokClickIdExtractor extends GenericClickIdExtractor
{
    public function __construct(CookieFactoryInterface $cookieFactory, CookieRepositoryInterface $cookieRepository, array $options = [])
    {
        parent::__construct($cookieFactory, $cookieRepository, Parameters::TIK_TOK_CLICK_ID, $options);
    }

    public function extract(Request $request): ?string
    {
        if (null !== $value = parent::extract($request)) {
            return $value;
        }

        // fall back to a "ttclid" cookie if we had not caught the query parameter
        if (!$request->cookies->has(Parameters::TIK_TOK_CLICK_ID)) {
            return null;
        }

        return (string) $request->cookies->get(Parameters::TIK_TOK_CLICK_ID);
    }
}
