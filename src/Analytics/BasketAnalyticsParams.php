<?php

declare(strict_types=1);

namespace izi\prestashop\Analytics;

/**
 * @final
 */
class BasketAnalyticsParams implements BasketAnalyticsInterface
{
    /**
     * @var array<string, string|null>
     */
    private $parameters = [];

    public function __construct()
    {
        if ([] === $args = func_get_args()) {
            return;
        }

        @trigger_error(\sprintf('Passing $gclid, $fbclid and $client_id as arguments of "%s()" is deprecated since version 2.6 / 3.2, set parameters by name using "withParameter()" instead.', __METHOD__), \E_USER_DEPRECATED);

        foreach ([Parameters::GOOGLE_CLICK_ID, Parameters::FACEBOOK_CLICK_ID, Parameters::GOOGLE_CLIENT_ID] as $i => $name) {
            if (!\array_key_exists($i, $args)) {
                continue;
            }

            $value = $args[$i];

            if (null !== $value && !\is_string($value)) {
                throw new \InvalidArgumentException(\sprintf('Expected argument #%d of "%s()" to be a string or null, "%s" given.', $i, __METHOD__, get_debug_type($value)));
            }

            $this->parameters[$name] = $value;
        }
    }

    /**
     * @return array<string, string|null>
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    public function getParameter(string $name): ?string
    {
        return $this->parameters[$name] ?? null;
    }

    public function withParameter(string $name, ?string $value): self
    {
        $params = clone $this;
        $params->parameters[$name] = $value;

        return $params;
    }

    public function getGclid(): ?string
    {
        return $this->getWithDeprecation(Parameters::GOOGLE_CLICK_ID, __METHOD__);
    }

    public function getFbclid(): ?string
    {
        return $this->getWithDeprecation(Parameters::FACEBOOK_CLICK_ID, __METHOD__);
    }

    public function getClientId(): ?string
    {
        return $this->getWithDeprecation(Parameters::GOOGLE_CLIENT_ID, __METHOD__);
    }

    public function isEmpty(): bool
    {
        return [] === array_filter($this->parameters, static function (?string $v) {
            return null !== $v;
        });
    }

    private function getWithDeprecation(string $name, string $method): ?string
    {
        @trigger_error(\sprintf('The method "%s()" is deprecated since version 2.6 / 3.2, use "%s::getParameter()" instead.', $method, self::class), \E_USER_DEPRECATED);

        return $this->getParameter($name);
    }
}
