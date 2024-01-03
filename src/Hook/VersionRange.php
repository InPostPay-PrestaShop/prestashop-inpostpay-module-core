<?php

declare(strict_types=1);

namespace izi\prestashop\Hook;

final class VersionRange
{
    private $min;
    private $max;

    public function __construct(?string $min, string $max = null)
    {
        if (null === $min && null === $max) {
            throw new \DomainException('Either min version, max version or both must be specified.');
        }

        if (null !== $min && null !== $max && !\Tools::version_compare($min, $max)) {
            throw new \DomainException('Max version must be greater than the min version.');
        }

        $this->min = $min;
        $this->max = $max;
    }

    public function getMinVersion(): ?string
    {
        return $this->min;
    }

    public function getMaxVersion(): ?string
    {
        return $this->max;
    }

    public function contains(string $version): bool
    {
        $minVersion = $this->getMinVersion();
        if (null !== $minVersion && \Tools::version_compare($version, $minVersion)) {
            return false;
        }

        $maxVersion = $this->getMaxVersion();

        return null === $maxVersion || \Tools::version_compare($version, $maxVersion);
    }
}
