<?php

declare(strict_types=1);

namespace izi\prestashop\Analytics;

/**
 * @final
 */
class BasketAnalytics implements BasketAnalyticsInterface
{
    /**
     * @var int
     */
    private $cartId;

    /**
     * @var string|null
     */
    private $gclid;

    /**
     * @var string|null
     */
    private $fbclid;

    /**
     * @var string|null
     */
    private $client_id;

    /**
     * @var string|null
     */
    private $ttclid;

    public function __construct(int $cartId, ?string $gclid, ?string $fbclid, ?string $client_id, ?string $ttclid = null)
    {
        $this->cartId = $cartId;
        $this->gclid = $gclid;
        $this->fbclid = $fbclid;
        $this->client_id = $client_id;
        $this->ttclid = $ttclid;
    }

    /**
     * @param array<string, string> $parameters
     */
    public static function fromParameters(int $cartId, array $parameters): self
    {
        return new self(
            $cartId,
            $parameters[Parameters::GOOGLE_CLICK_ID] ?? null,
            $parameters[Parameters::FACEBOOK_CLICK_ID] ?? null,
            $parameters[Parameters::GOOGLE_CLIENT_ID] ?? null,
            $parameters[Parameters::TIK_TOK_CLICK_ID] ?? null
        );
    }

    /**
     * @internal
     *
     * @return array<string, string|null>
     */
    public static function doGetParameters(BasketAnalyticsInterface $analytics): array
    {
        if (method_exists($analytics, 'getParameters')) {
            return $analytics->getParameters();
        }

        @trigger_error(\sprintf('Not implementing the method "getParameters()" in "%s" is deprecated since version 3.2.', \get_class($analytics)), \E_USER_DEPRECATED);

        return [
            Parameters::GOOGLE_CLICK_ID => $analytics->getGclid(),
            Parameters::FACEBOOK_CLICK_ID => $analytics->getFbclid(),
            Parameters::GOOGLE_CLIENT_ID => $analytics->getClientId(),
        ];
    }

    public function getCartId(): int
    {
        return $this->cartId;
    }

    /**
     * @return array<string, string|null>
     */
    public function getParameters(): array
    {
        return [
            Parameters::GOOGLE_CLICK_ID => $this->gclid,
            Parameters::FACEBOOK_CLICK_ID => $this->fbclid,
            Parameters::GOOGLE_CLIENT_ID => $this->client_id,
            Parameters::TIK_TOK_CLICK_ID => $this->ttclid,
        ];
    }

    public function getParameter(string $name): ?string
    {
        switch ($name) {
            case Parameters::GOOGLE_CLICK_ID:
            case Parameters::FACEBOOK_CLICK_ID:
            case Parameters::GOOGLE_CLIENT_ID:
            case Parameters::TIK_TOK_CLICK_ID:
                return $this->{$name};
            default:
                return null;
        }
    }

    /**
     * @return bool whether the value was stored
     */
    public function setParameter(string $name, ?string $value): bool
    {
        switch ($name) {
            case Parameters::GOOGLE_CLICK_ID:
            case Parameters::FACEBOOK_CLICK_ID:
            case Parameters::GOOGLE_CLIENT_ID:
            case Parameters::TIK_TOK_CLICK_ID:
                $this->{$name} = $value;

                return true;
            default:
                return false;
        }
    }

    public function getGclid(): ?string
    {
        return $this->gclid;
    }

    public function getFbclid(): ?string
    {
        return $this->fbclid;
    }

    public function getClientId(): ?string
    {
        return $this->client_id;
    }

    public function getTikTokClickId(): ?string
    {
        return $this->ttclid;
    }

    public function isEmpty(): bool
    {
        return null === $this->gclid
            && null === $this->fbclid
            && null === $this->client_id
            && null === $this->ttclid;
    }
}
