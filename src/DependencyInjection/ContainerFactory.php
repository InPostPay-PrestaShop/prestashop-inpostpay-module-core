<?php

declare(strict_types=1);

namespace izi\prestashop\DependencyInjection;

use izi\prestashop\DependencyInjection\Compiler\FilterResourcesPass;
use izi\prestashop\DependencyInjection\Dumper\PhpDumper;
use izi\prestashop\Hook\Adapter\HookDispatcher;
use izi\prestashop\Hook\HookDispatcherInterface;
use Symfony\Component\Config\ConfigCache;
use Symfony\Component\Config\ConfigCacheInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Loader\DelegatingLoader;
use Symfony\Component\Config\Loader\LoaderResolver;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Loader\ClosureLoader;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

/**
 * @internal
 */
final class ContainerFactory
{
    private const HOOK_NAME = 'actionInPostIziBuildContainer';

    private $cacheDir;
    private $hookDispatcher;

    public function __construct(string $cacheDir, ?HookDispatcherInterface $hookDispatcher = null)
    {
        $this->cacheDir = rtrim($cacheDir, '/');
        $this->hookDispatcher = $hookDispatcher ?? new HookDispatcher();
    }

    /**
     * @template T of ContainerInterface
     *
     * @param class-string<T> $className
     *
     * @return T
     */
    public function create(string $className, iterable $resources, string $type): ContainerInterface
    {
        [$namespace, $shortName] = $this->resolveClassName($className);
        $cachePath = sprintf('%s/%s.php', $this->cacheDir, $shortName);
        $cache = new ConfigCache($cachePath, _PS_MODE_DEV_);

        if (!$cache->isFresh()) {
            $container = $this->buildContainer($resources, $type);

            $this->dumpContainer($container, $cache, $namespace, $shortName);
        }

        require_once $cachePath;

        $container = new $className();
        $this->includeAutoloaders($container);

        return $container;
    }

    private function resolveClassName(string $className): array
    {
        if (false === $pos = strrpos($className, '\\')) {
            return ['', $className];
        }

        return [
            substr($className, 0, $pos),
            substr($className, $pos + 1),
        ];
    }

    private function buildContainer(iterable $resources, string $type): ContainerBuilder
    {
        $container = new ContainerBuilder();

        $container->addResource(new FileResource(__FILE__));

        $container->setParameter('kernel.root_dir', _PS_ROOT_DIR_ . '/app');
        $container->setParameter('kernel.project_dir', _PS_ROOT_DIR_);
        $container->setParameter('kernel.cache_dir', _PS_CACHE_DIR_);
        $container->setParameter('kernel.debug', _PS_MODE_DEV_);
        $container->setParameter('kernel.environment', _PS_MODE_DEV_ ? 'dev' : 'prod');

        $fileLocator = new FileLocator();
        $loader = new DelegatingLoader(new LoaderResolver([
            new YamlFileLoader($container, $fileLocator),
            new XmlFileLoader($container, $fileLocator),
            new PhpFileLoader($container, $fileLocator),
            new ClosureLoader($container),
        ]));

        foreach ($resources as $resource) {
            $loader->load($resource);
        }

        if (method_exists($this->hookDispatcher, 'getListenerNames')) {
            $container->setParameter('inpost.izi.extension_modules', $this->hookDispatcher->getListenerNames(self::HOOK_NAME));
        }

        $this->hookDispatcher->dispatch(self::HOOK_NAME, [
            'loader' => $loader,
            'type' => $type,
        ]);

        $container->addCompilerPass(new FilterResourcesPass(), PassConfig::TYPE_BEFORE_REMOVING);
        $container->isCompiled() || $container->compile(false);

        return $container;
    }

    private function dumpContainer(ContainerBuilder $container, ConfigCacheInterface $cache, string $namespace, string $className): void
    {
        $dumper = new PhpDumper($container);
        $cache->write(
            $dumper->dump([
                'namespace' => $namespace,
                'class' => $className,
                'debug' => false,
            ]),
            $container->getResources()
        );
    }

    private function includeAutoloaders(ContainerInterface $container): void
    {
        if (!$container->hasParameter('inpost.izi.extension_modules')) {
            return;
        }

        foreach ($container->getParameter('inpost.izi.extension_modules') as $name) {
            $path = \sprintf('%s%s/vendor/autoload.php', _PS_MODULE_DIR_, $name);
            if (file_exists($path)) {
                include_once $path;
            }
        }
    }
}
