<?php

declare(strict_types=1);

namespace izi\prestashop\Common\Order;

final class Consent implements \JsonSerializable
{
    /**
     * @var string
     */
    private $consent_id;

    /**
     * @var string
     */
    private $consent_version;

    /**
     * @var bool
     */
    private $is_accepted;

    public function __construct(string $consent_id, string $consent_version, bool $is_accepted)
    {
        $this->consent_id = $consent_id;
        $this->consent_version = $consent_version;
        $this->is_accepted = $is_accepted;
    }

    public function getId(): string
    {
        return $this->consent_id;
    }

    public function getVersion(): string
    {
        return $this->consent_version;
    }

    public function isAccepted(): bool
    {
        return $this->is_accepted;
    }

    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
