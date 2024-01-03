<?php

declare(strict_types=1);

namespace izi\prestashop\MerchantApi\Model\Basket\Request;

final class Browser implements \JsonSerializable
{
    /**
     * @var bool
     */
    private $browser_trusted;

    /**
     * @var string|null
     */
    private $browser_id;

    public function __construct(bool $browser_trusted, string $browser_id = null)
    {
        $this->browser_trusted = $browser_trusted;
        $this->browser_id = $browser_id;
    }

    public function isTrusted(): bool
    {
        return $this->browser_trusted;
    }

    public function getId(): ?string
    {
        return $this->browser_id;
    }

    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
