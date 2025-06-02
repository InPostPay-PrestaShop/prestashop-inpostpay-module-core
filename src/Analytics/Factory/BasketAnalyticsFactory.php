<?php

declare(strict_types=1);

namespace izi\prestashop\Analytics\Factory;

use izi\prestashop\Analytics\BasketAnalyticsInterface;
use izi\prestashop\Analytics\BasketAnalyticsParams;
use izi\prestashop\Analytics\Cookie\CookieExtractorInterface;
use Symfony\Component\HttpFoundation\Request;

final class BasketAnalyticsFactory implements BasketAnalyticsFactoryInterface
{
    /**
     * @var CookieExtractorInterface
     */
    private $gclidExtractor;

    /**
     * @var CookieExtractorInterface
     */
    private $fbclidExtractor;

    /**
     * @var CookieExtractorInterface
     */
    private $clientIdExtractor;

    public function __construct(
        CookieExtractorInterface $gclidExtractor,
        CookieExtractorInterface $fbclidExtractor,
        CookieExtractorInterface $clientIdExtractor
    ) {
        $this->gclidExtractor = $gclidExtractor;
        $this->fbclidExtractor = $fbclidExtractor;
        $this->clientIdExtractor = $clientIdExtractor;
    }

    public function createFromRequest(Request $request): BasketAnalyticsInterface
    {
        $gclid = $this->gclidExtractor->extract($request);
        $fbclid = $this->fbclidExtractor->extract($request);
        $clientId = $this->clientIdExtractor->extract($request);

        return new BasketAnalyticsParams($gclid, $fbclid, $clientId);
    }
}
