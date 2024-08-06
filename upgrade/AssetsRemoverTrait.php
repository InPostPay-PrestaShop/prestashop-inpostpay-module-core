<?php

declare(strict_types=1);

namespace InPost\Izi\Upgrade;

use Symfony\Component\Filesystem\Filesystem;

trait AssetsRemoverTrait
{
    /**
     * @var \Module
     */
    private $module;

    private function removeStaleAssets(array $paths): bool
    {
        $basePath = sprintf('%s/views', rtrim($this->module->getLocalPath(), '/'));
        $files = array_map(static function (string $path) use ($basePath): string {
            return sprintf('%s/%s', $basePath, $path);
        }, $paths);

        (new Filesystem())->remove($files);

        return true;
    }
}
