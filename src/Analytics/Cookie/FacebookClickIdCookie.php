<?php

declare(strict_types=1);

namespace izi\prestashop\Analytics\Cookie;

use izi\prestashop\Analytics\Cookie\Factory\CookieFactoryInterface;
use izi\prestashop\Analytics\Cookie\Repository\CookieRepositoryInterface;
use Symfony\Component\HttpFoundation\Request;

final class FacebookClickIdCookie implements CookieExtractorInterface, CookiePersisterInterface, CookieEraserInterface
{
    private const COOKIE_NAME = 'izi_fbclid';
    private const PARAMETER = 'fbclid';
    private const COOKIE_EXPIRE_TIME = 3600;

    /**
     * @var CookieFactoryInterface
     */
    private $cookieFactory;

    /**
     * @var CookieRepositoryInterface
     */
    private $cookieRepository;

    public function __construct(
        CookieFactoryInterface $cookieFactory,
        CookieRepositoryInterface $cookieRepository
    ) {
        $this->cookieFactory = $cookieFactory;
        $this->cookieRepository = $cookieRepository;
    }

    public function extract(Request $request): ?string
    {
        if ($request->cookies->has(self::COOKIE_NAME)) {
            return $request->cookies->get(self::COOKIE_NAME);
        }

        return null;
    }

    public function persist(Request $request): void
    {
        $parameter = $request->query->get(self::PARAMETER);

        if (null === $parameter) {
            return;
        }

        $cookie = $this->cookieFactory->create(self::COOKIE_NAME, $parameter, time() + self::COOKIE_EXPIRE_TIME);

        $this->cookieRepository->persist($cookie);
    }

    public function erase(Request $request): void
    {
        if ($request->cookies->has(self::COOKIE_NAME)) {
            $cookie = $this->cookieFactory->create(self::COOKIE_NAME, '', -1);

            unset($_COOKIE[self::COOKIE_NAME]);
            $this->cookieRepository->persist($cookie);
        }
    }
}
