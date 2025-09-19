<?php

declare(strict_types=1);

namespace izi\prestashop\CacheClearer;

use Symfony\Component\Filesystem\Filesystem;

final class SymfonyCacheClearer implements CacheClearerInterface
{
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
        if (\Tools::version_compare(_PS_VERSION_, '1.7.6')) {
            register_shutdown_function(function () {
                $this->removeCacheDirectory('prod');
                $this->removeCacheDirectory('dev');
            });

            return;
        }

        if (\Tools::version_compare(_PS_VERSION_, '1.7.8')) {
            \Tools::clearSf2Cache('prod');
            \Tools::clearSf2Cache('dev');

            return;
        }

        register_shutdown_function(function () {
            $this->removeCacheDirectory('prod' === _PS_ENV_ ? 'dev' : 'prod');
        });

        /* @see \Module::$_INSTANCE if not empty when dumping container metadata on PHP versions before 8.1, might cause the script to exceed the memory limit
         * in {@see \Symfony\Component\Config\Resource\ReflectionClassResource::generateSignature()} due to https://bugs.php.net/bug.php?id=80821 */
        if (80100 > PHP_VERSION_ID) {
            register_shutdown_function(self::getStaticCircularReferencesClearer());
        }

        \Tools::clearSf2Cache();
    }

    private function removeCacheDirectory(string $env = _PS_ENV_): void
    {
        $dir = sprintf('%s/%s', dirname(_PS_CACHE_DIR_), $env);

        if (\Tools::version_compare(_PS_VERSION_, '1.7.6')) {
            $dir .= '/inpost/izi';
        }

        $this->filesystem->remove($dir);
    }

    private function __clone()
    {
    }

    private static function getStaticCircularReferencesClearer(): callable
    {
        if (is_callable([\Module::class, 'resetStaticCache'])) {
            return [\Module::class, 'resetStaticCache'];
        }

        return \Closure::bind(static function () {
            \Module::$_INSTANCE = [];
        }, null, \Module::class);
    }
}
