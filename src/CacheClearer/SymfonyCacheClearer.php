<?php

declare(strict_types=1);

namespace izi\prestashop\CacheClearer;

use PrestaShop\PrestaShop\Adapter\SymfonyContainer;
use Symfony\Component\Filesystem\Filesystem;

final class SymfonyCacheClearer implements CacheClearerInterface
{
    private const LEGACY_CONTAINER_FILES = [
        'FrontContainer.php',
        'FrontContainer.php.meta',
        'WebserviceContainer.php',
        'WebserviceContainer.php.meta',
    ];

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var bool
     */
    private $registered = false;

    /**
     * @var self
     */
    private static $instance;

    private function __construct()
    {
        $this->filesystem = new Filesystem();
    }

    public static function getInstance(): self
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Dumping container metadata on PHP versions before 8.1 might cause the script to exceed memory limit
     * in @see \Symfony\Component\Config\Resource\ReflectionClassResource::generateSignature()
     * due to https://bugs.php.net/bug.php?id=80821 if @see \Module::$_INSTANCE is not empty.
     *
     * @internal
     */
    public static function getStaticCircularReferencesClearer(): ?callable
    {
        if (80100 <= \PHP_VERSION_ID) {
            return null;
        }

        if (\is_callable([\Module::class, 'resetStaticCache'])) {
            return [\Module::class, 'resetStaticCache'];
        }

        return \Closure::bind(static function () {
            \Module::$_INSTANCE = [];
        }, null, \Module::class);
    }

    public function clear(): void
    {
        if ($this->registered) {
            return;
        }

        $this->registered = true;
        $this->registerCacheClear();
    }

    private function registerCacheClear(): void
    {
        if (\Tools::version_compare(_PS_VERSION_, '1.7.8')) {
            \Tools::clearSf2Cache('prod');
            \Tools::clearSf2Cache('dev');

            return;
        }

        if (null !== $clearer = self::getStaticCircularReferencesClearer()) {
            register_shutdown_function($clearer);
        }

        if (\Tools::version_compare(_PS_VERSION_, '9.0.0') || null === SymfonyContainer::getInstance()) {
            register_shutdown_function(function () {
                $this->removeCacheDirectory('prod' === _PS_ENV_ ? 'dev' : 'prod');
            });
        }

        if ('9.0.0' === _PS_VERSION_) {
            register_shutdown_function(function () {
                $this->removeLegacyContainers('dev');
                $this->removeLegacyContainers('prod');
            });
        }

        \Tools::clearSf2Cache();
    }

    private function removeCacheDirectory(string $env): void
    {
        $dir = $this->getCacheDir($env);

        $this->filesystem->remove($dir);
    }

    private function removeLegacyContainers(string $env): void
    {
        $dir = $this->getCacheDir($env);
        $files = array_map(static function (string $filename) use ($dir): string {
            return $dir . '/' . $filename;
        }, self::LEGACY_CONTAINER_FILES);

        $this->filesystem->remove($files);
    }

    private function getCacheDir(string $env): string
    {
        return \sprintf('%s/%s', \dirname(_PS_CACHE_DIR_), $env);
    }

    private function __clone()
    {
    }
}
