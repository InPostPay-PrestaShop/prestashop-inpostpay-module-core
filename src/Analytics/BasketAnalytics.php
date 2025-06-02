<?php

declare(strict_types=1);

namespace izi\prestashop\Analytics;

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

    public function __construct(int $cartId, ?string $gclid, ?string $fbclid, ?string $client_id)
    {
        $this->cartId = $cartId;
        $this->gclid = $gclid;
        $this->fbclid = $fbclid;
        $this->client_id = $client_id;
    }

    public function getCartId(): int
    {
        return $this->cartId;
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

    public function isEmpty(): bool
    {
        return null === $this->gclid && null === $this->fbclid && null === $this->client_id;
    }
}
