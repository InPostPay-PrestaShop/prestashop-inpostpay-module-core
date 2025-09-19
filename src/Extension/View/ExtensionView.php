<?php

declare(strict_types=1);

namespace izi\prestashop\Extension\View;

final class ExtensionView
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $version;

    /**
     * @var string
     */
    private $url;

    /**
     * @var string|null
     */
    private $description;

    /**
     * @var string|null
     */
    private $module;

    /**
     * @var bool
     */
    private $upgradeable = false;

    public function __construct(string $name, string $version, string $url, ?string $description = null, ?string $module = null)
    {
        $this->name = $name;
        $this->version = $version;
        $this->url = $url;
        $this->description = $description;
        $this->module = $module;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getModule(): ?string
    {
        return $this->module;
    }

    public function isUpgradeable(): bool
    {
        return $this->upgradeable;
    }

    public function setUpgradeable(bool $upgradeable): void
    {
        $this->upgradeable = $upgradeable;
    }
}
