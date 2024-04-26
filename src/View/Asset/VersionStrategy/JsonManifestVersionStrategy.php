<?php

declare(strict_types=1);

namespace izi\prestashop\View\Asset\VersionStrategy;

use Symfony\Component\Asset\VersionStrategy\VersionStrategyInterface;

/**
 * Polyfill for Sf 2.8
 *
 * @see \Symfony\Component\Asset\VersionStrategy\JsonManifestVersionStrategy
 */
final class JsonManifestVersionStrategy implements VersionStrategyInterface
{
    private $manifestPath;
    private $manifestData;

    /**
     * @param string $manifestPath Absolute path to the manifest file
     */
    public function __construct(string $manifestPath)
    {
        $this->manifestPath = $manifestPath;
    }

    /**
     * With a manifest, we don't really know or care about what
     * the version is. Instead, this returns the path to the
     * versioned file.
     */
    public function getVersion($path): string
    {
        return $this->applyVersion($path);
    }

    public function applyVersion($path): string
    {
        return $this->getManifestPath($path) ?: $path;
    }

    private function getManifestPath(string $path): ?string
    {
        if (null === $this->manifestData) {
            if (!file_exists($this->manifestPath)) {
                throw new \RuntimeException(sprintf('Asset manifest file "%s" does not exist. Did you forget to build the assets with npm or yarn?', $this->manifestPath));
            }

            $this->manifestData = json_decode(file_get_contents($this->manifestPath), true);
            if (0 < json_last_error()) {
                throw new \RuntimeException(sprintf('Error parsing JSON from asset manifest file "%s": ', $this->manifestPath) . json_last_error_msg());
            }
        }

        return $this->manifestData[$path] ?? null;
    }
}
