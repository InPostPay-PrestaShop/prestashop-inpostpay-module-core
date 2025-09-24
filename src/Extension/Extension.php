<?php

declare(strict_types=1);

namespace izi\prestashop\Extension;

final class Extension
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var ExtensionVersion[]
     */
    private $versions;

    /**
     * @var string|null
     */
    private $module;

    /**
     * @var string[]
     */
    private $displayName;

    /**
     * @var string[]
     */
    private $description;

    /**
     * @param ExtensionVersion[] $versions
     * @param array<string, string> $displayName display name by language code
     * @param array<string, string> $description description by language code
     */
    public function __construct(string $name, array $versions, ?string $module = null, array $displayName = [], array $description = [])
    {
        $this->name = $name;
        $this->versions = $versions;
        $this->module = $module;
        $this->displayName = $displayName;
        $this->description = $description;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return ExtensionVersion[]
     */
    public function getVersions(): array
    {
        return $this->versions;
    }

    /**
     * @param ExtensionVersion[] $versions
     */
    public function withVersions(array $versions): self
    {
        $extension = clone $this;
        $extension->versions = $versions;

        return $extension;
    }

    public function getModule(): ?string
    {
        return $this->module;
    }

    public function getDisplayName(string $languageCode): ?string
    {
        return $this->displayName[$languageCode] ?? null;
    }

    public function getDescription(string $languageCode): ?string
    {
        return $this->description[$languageCode] ?? null;
    }
}
