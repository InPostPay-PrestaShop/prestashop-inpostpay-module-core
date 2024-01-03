<?php

declare(strict_types=1);

namespace izi\prestashop\BasketApp\Basket\Response;

final class QrCode implements \JsonSerializable
{
    /**
     * @var string
     */
    private $qr_code;

    /**
     * @var string
     */
    private $deep_link;

    /**
     * @var string
     */
    private $deep_link_hms;

    public function __construct(string $qr_code, string $deep_link, string $deep_link_hms)
    {
        $this->qr_code = $qr_code;
        $this->deep_link = $deep_link;
        $this->deep_link_hms = $deep_link_hms;
    }

    public function getQrCode(): string
    {
        return $this->qr_code;
    }

    public function getDeepLink(): string
    {
        return $this->deep_link;
    }

    public function getDeepLinkHms(): string
    {
        return $this->deep_link_hms;
    }

    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
