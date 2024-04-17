<?php

declare(strict_types=1);

namespace izi\prestashop\Uuid;

abstract class Uuid implements \JsonSerializable
{
    private $uuid;

    public function __construct(?string $uuid = null)
    {
        if (null === $uuid) {
            $this->uuid = static::generate();
        } else {
            $version = preg_match('/^[0-9a-f]{8}(?:-[0-9a-f]{4}){3}-[0-9a-f]{12}$/Di', $uuid) ? (int) $uuid[14] : false;

            if (false === $version || $version !== static::getVersion()) {
                throw new \DomainException(sprintf('Invalid UUIDv%d: "%s".', static::getVersion(), $uuid));
            }

            $this->uuid = strtolower($uuid);
        }
    }

    public static function v4(): UuidV4
    {
        return new UuidV4();
    }

    public function __toString(): string
    {
        return $this->uuid;
    }

    public function jsonSerialize(): string
    {
        return $this->uuid;
    }

    abstract protected static function generate(): string;

    abstract protected static function getVersion(): int;
}
