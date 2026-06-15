<?php

declare(strict_types=1);

namespace InPost\Izi\Upgrade;

use Symfony\Component\Filesystem\Filesystem;

/**
 * @internal
 */
trait FileRemoverTrait
{
    private static $namespacePrefix = 'izi\\prestashop\\';

    /**
     * @var \Module
     */
    private $module;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @param string[] $paths
     */
    private function removeFiles(array $paths): bool
    {
        $basePath = rtrim($this->module->getLocalPath(), '/');

        $files = array_map(static function (string $path) use ($basePath): string {
            return sprintf('%s/%s', $basePath, $path);
        }, $paths);

        $this->getFileSystem()->remove($files);

        return true;
    }

    /**
     * @param string[] $classes
     */
    private function removeClasses(array $classes): bool
    {
        $paths = array_map(static function (string $class): string {
            $class = str_replace(self::$namespacePrefix, '', $class);

            return 'src/' . str_replace('\\', DIRECTORY_SEPARATOR, $class) . '.php';
        }, $classes);

        return $this->removeFiles($paths);
    }

    private function getFileSystem(): Filesystem
    {
        return $this->filesystem ?? $this->filesystem = new Filesystem();
    }
}
