<?php

declare(strict_types=1);

namespace InPost\Izi\Upgrade;

use Symfony\Component\Filesystem\Filesystem;

final class CacheClearer
{
    /**
     * @var Filesystem
     */
    private $filesystem;

    private $registered = false;

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

    public function clear(): void
    {
        if ($this->registered) {
            return;
        }

        $this->registered = true;

        register_shutdown_function(function () {
            $this->doClear();
            $this->registered = false;
        });
    }

    private function doClear(): void
    {
        if (\Tools::version_compare(_PS_VERSION_, '1.7.1')) {
            $this->removeCacheDirectory('prod');
            $this->removeCacheDirectory('dev');

            return;
        }

        if (\Tools::version_compare(_PS_VERSION_, '8.0.0')) {
            \Tools::clearSf2Cache('prod');
            \Tools::clearSf2Cache('dev');

            return;
        }

        if ('prod' !== _PS_ENV_) {
            $this->removeCacheDirectory('prod');
        }

        /* @see \Module::$_INSTANCE if not empty when dumping container metadata on PHP versions before 8.1, might cause the script to exceed memory limit
         * in {@see \Symfony\Component\Config\Resource\ReflectionClassResource::generateSignature()} due to https://bugs.php.net/bug.php?id=80821 */
        if (80100 > PHP_VERSION_ID) {
            \Module::resetStaticCache();
        }

        \Tools::clearSf2Cache();
    }

    private function removeCacheDirectory(string $env): void
    {
        $dir = sprintf('%s/%s', dirname(_PS_CACHE_DIR_), $env);

        $this->filesystem->remove($dir);
    }
}
