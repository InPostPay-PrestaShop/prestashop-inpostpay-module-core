<?php

declare(strict_types=1);

namespace izi\prestashop\Routing;

use Symfony\Component\Config\FileLocatorInterface;
use Symfony\Component\Config\Loader\FileLoader;
use Symfony\Component\Config\Loader\LoaderResolverInterface;
use Symfony\Component\Routing\Loader\AnnotationDirectoryLoader as DecoratedLoader;
use Symfony\Component\Routing\RouteCollection;

/**
 * Bypasses trying to locate directory by relative path Sf 2.8 (current path is not taken into account when checking if resource can be imported).
 *
 * @internal
 */
final class AnnotationDirectoryLoader extends FileLoader
{
    /**
     * @var FileLoader
     */
    private $loader;

    /**
     * @param DecoratedLoader $loader
     */
    public function __construct(FileLoader $loader)
    {
        $this->loader = $loader;
        parent::__construct($loader->getLocator());
    }

    public function load($resource, $type = null): RouteCollection
    {
        return $this->loader->load($resource);
    }

    public function supports($resource, $type = null): bool
    {
        if ('annotation' === $type) {
            return true;
        }

        return $this->loader->supports($resource);
    }

    public function getResolver(): LoaderResolverInterface
    {
        return $this->loader->getResolver();
    }

    public function setResolver(LoaderResolverInterface $resolver): void
    {
        $this->loader->setResolver($resolver);
    }

    /**
     * @param string $dir
     */
    public function setCurrentDir($dir): void
    {
        $this->loader->setCurrentDir($dir);
    }

    public function getLocator(): FileLocatorInterface
    {
        return $this->loader->getLocator();
    }

    public function import($resource, $type = null, $ignoreErrors = false, $sourceResource = null)
    {
        return $this->loader->import($resource, $type, $ignoreErrors, $sourceResource);
    }
}
