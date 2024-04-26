<?php

declare(strict_types=1);

namespace izi\prestashop\Twig\Loader;

/**
 * @internal
 */
trait TemplateNameMappingLoaderDecoratorTrait
{
    /**
     * @var \Twig_LoaderInterface&\Twig_ExistsLoaderInterface
     */
    private $loader;

    /**
     * @var array<string, string>
     */
    private $templateNamesMap;

    /**
     * @param \Twig_LoaderInterface&\Twig_ExistsLoaderInterface $loader
     * @param array<string, string> $templatesMap
     */
    public function __construct(\Twig_LoaderInterface $loader, array $templatesMap)
    {
        $this->loader = $loader;
        $this->templateNamesMap = $templatesMap;
    }

    public function exists($name): bool
    {
        $name = $this->getMappedTemplateName($name);

        return $this->loader->exists($name);
    }

    public function getSource($name): string
    {
        $name = $this->getMappedTemplateName($name);

        return $this->loader->getSource($name);
    }

    public function getCacheKey($name): string
    {
        $name = $this->getMappedTemplateName($name);

        return $this->loader->getCacheKey($name);
    }

    public function isFresh($name, $time): bool
    {
        $name = $this->getMappedTemplateName($name);

        return $this->loader->isFresh($name, $time);
    }

    private function getMappedTemplateName(string $name): string
    {
        return $this->templateNamesMap[$name] ?? $name;
    }
}
