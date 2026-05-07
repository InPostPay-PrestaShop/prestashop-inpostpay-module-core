<?php

declare(strict_types=1);

namespace InPost\Izi\Upgrade;

require_once __DIR__ . '/FileRemoverTrait.php';

trait AssetsRemoverTrait
{
    use FileRemoverTrait;

    /**
     * @param string[] $paths
     */
    private function removeStaleAssets(array $paths): bool
    {
        return $this->removeFiles(array_map(static function (string $path): string {
            return 'views/' . $path;
        }, $paths));
    }
}
