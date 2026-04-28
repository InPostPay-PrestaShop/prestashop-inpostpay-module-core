<?php

declare(strict_types=1);

namespace izi\prestashop\Analytics\Factory;

use izi\prestashop\Analytics\BasketAnalyticsInterface;
use izi\prestashop\Analytics\BasketAnalyticsParams;
use izi\prestashop\Analytics\Cookie\CookieExtractorInterface;
use izi\prestashop\Analytics\Parameters;
use Symfony\Component\HttpFoundation\Request;

final class BasketAnalyticsFactory implements BasketAnalyticsFactoryInterface
{
    private const PARAM_NAMES = [
        Parameters::GOOGLE_CLICK_ID,
        Parameters::FACEBOOK_CLICK_ID,
        Parameters::GOOGLE_CLIENT_ID,
    ];

    /**
     * @var iterable<CookieExtractorInterface>
     */
    private $extractors;

    /**
     * @var array<string, CookieExtractorInterface>
     */
    private $extractorsByName = [];

    /**
     * @param iterable<CookieExtractorInterface> $extractors
     */
    public function __construct($extractors)
    {
        if ($extractors instanceof CookieExtractorInterface) {
            @trigger_error(\sprintf('Passing $gclidExtractor, $fbclidExtractor and $clientIdExtractor as arguments of "%s()" is deprecated since version 3.2, pass extractors with the "getParameterName()" method implemented as an iterable instead.', __METHOD__), \E_USER_DEPRECATED);
            $args = func_get_args();
            $this->setExtractorsByName($args);
        } elseif (!is_iterable($extractors)) {
            throw new \InvalidArgumentException(\sprintf('Expected $extractors to be iterable, "%s" given.', get_debug_type($extractors)));
        } else {
            $this->extractors = $extractors;
        }
    }

    public function createFromRequest(Request $request): BasketAnalyticsInterface
    {
        $params = new BasketAnalyticsParams();

        foreach ($this->extractors as $i => $extractor) {
            if (method_exists($extractor, 'getParameterName')) {
                $name = $extractor->getParameterName();
            } else {
                @trigger_error(\sprintf('Not implementing the method "getParameterName()" in "%s" is deprecated since version 3.2.', \get_class($extractor)), \E_USER_DEPRECATED);

                if (!isset(self::PARAM_NAMES[$i])) {
                    continue;
                }

                $name = self::PARAM_NAMES[$i];
            }

            $value = $extractor->extract($request);
            $params = $params->withParameter($name, $value);
        }

        foreach ($this->extractorsByName as $name => $extractor) {
            $value = $extractor->extract($request);
            $params = $params->withParameter($name, $value);
        }

        return $params;
    }

    private function setExtractorsByName(array $args): void
    {
        $this->extractors = [];

        foreach (self::PARAM_NAMES as $i => $param) {
            if (!\array_key_exists($i, $args)) {
                break;
            }

            $extractor = $args[$i];

            if (!$extractor instanceof CookieExtractorInterface) {
                throw new \InvalidArgumentException(\sprintf('Expected argument #%d of "%s:__construct()" to be an instance of "%s", "%s" given.', $i, self::class, CookieExtractorInterface::class, get_debug_type($extractor)));
            }

            $this->extractorsByName[$param] = $extractor;
        }
    }
}
