<?php

declare(strict_types=1);

namespace izi\prestashop\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Loader\DelegatingLoader;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Config\Loader\LoaderResolver;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Loader\ClosureLoader;
use Symfony\Component\DependencyInjection\Loader\DirectoryLoader;
use Symfony\Component\DependencyInjection\Loader\GlobFileLoader;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\ParameterBag\EnvPlaceholderParameterBag;

final class ContainerFactory
{
    /**
     * @var Container
     */
    private $container;

    /**
     * @param Container $container app container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @param string[] $requiredParams params to copy from the app container
     */
    public function buildContainer(iterable $resources, array $requiredParams = [], bool $resolveEnvPlaceholders = true): ContainerInterface
    {
        $parameters = $this->getParameters($requiredParams);
        $parameterBag = new EnvPlaceholderParameterBag($parameters);
        $container = new ContainerBuilder($parameterBag);

        $loader = $this->getContainerLoader($container);

        foreach ($resources as $resource) {
            $loader->load($resource);
        }

        $container->register('app_container', ContainerInterface::class)
            ->setSynthetic(true)
            ->setPublic(true);

        $container->compile($resolveEnvPlaceholders);
        $container->set('app_container', $this->container);

        return $container;
    }

    private function getContainerLoader(ContainerBuilder $container): LoaderInterface
    {
        $locator = new FileLocator();
        $resolver = new LoaderResolver([
            new XmlFileLoader($container, $locator),
            new YamlFileLoader($container, $locator),
            new PhpFileLoader($container, $locator),
            new GlobFileLoader($container, $locator),
            new DirectoryLoader($container, $locator),
            new ClosureLoader($container),
        ]);

        return new DelegatingLoader($resolver);
    }

    /**
     * The `getParameterBag()` method may fail if parameters depend on env variables that are not set. Some modules
     * load default values from .env files, but in some cases the related code may not have been executed yet
     * (e.g. in earlier versions of "ps_edition_basic", the defaults are loaded before module class declaration).
     *
     * @param string[] $requiredParams
     *
     * @return array<string, mixed>
     */
    private function getParameters(array $requiredParams): array
    {
        try {
            return $this->container->getParameterBag()->all();
        } catch (\Exception $e) {
            return array_map(function (string $name) {
                return $this->container->getParameter($name);
            }, array_combine($requiredParams, $requiredParams));
        }
    }
}
