<?php

declare(strict_types=1);

namespace izi\prestashop\Extension;

final class ExtensionVersion
{
    /**
     * @var string
     */
    private $version;

    /**
     * @var string
     */
    private $url;

    /**
     * @var string
     */
    private $checksum;

    /**
     * @var string[]
     */
    private $require;

    /**
     * @param array<string, string> $require versions constraint by dependency name
     */
    public function __construct(string $version, string $url, string $checksum, array $require = [])
    {
        $this->version = $version;
        $this->url = $url;
        $this->checksum = $checksum;
        $this->require = $require;
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getChecksum(): string
    {
        return $this->checksum;
    }

    public function getDependencies(): array
    {
        return $this->require;
    }

    public function getVersionsConstraint(string $name): ?string
    {
        return $this->require[$name] ?? null;
    }
}
