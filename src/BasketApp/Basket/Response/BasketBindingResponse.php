<?php

declare(strict_types=1);

namespace izi\prestashop\BasketApp\Basket\Response;

final class BasketBindingResponse implements \JsonSerializable
{
    /**
     * @var bool
     */
    private $basket_linked;

    /**
     * @var bool
     */
    private $browser_trusted;

    /**
     * @var string|null
     */
    private $inpost_basket_id;

    /**
     * @var ClientDetails|null
     */
    private $client_details;

    public function __construct(bool $basket_linked, bool $browser_trusted, string $inpost_basket_id = null, ClientDetails $client_details = null)
    {
        $this->basket_linked = $basket_linked;
        $this->browser_trusted = $browser_trusted;
        $this->inpost_basket_id = $inpost_basket_id;
        $this->client_details = $client_details;
    }

    public function isBasketLinked(): bool
    {
        return $this->basket_linked;
    }

    public function isBrowserTrusted(): bool
    {
        return $this->browser_trusted;
    }

    public function getInPostBasketId(): ?string
    {
        return $this->inpost_basket_id;
    }

    public function getClientDetails(): ?ClientDetails
    {
        return $this->client_details;
    }

    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
