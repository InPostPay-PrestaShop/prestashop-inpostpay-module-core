<?php

declare(strict_types=1);

namespace izi\prestashop\Twig\Loader;

if (interface_exists(\Twig_SourceContextLoaderInterface::class)) {
    /**
     * Maps template names to their Sf 2.8 replacements.
     *
     * @internal
     */
    final class TemplateNameMappingLoader implements \Twig_LoaderInterface, \Twig_ExistsLoaderInterface, \Twig_SourceContextLoaderInterface
    {
        use TemplateNameMappingLoaderDecoratorTrait;

        public function getSourceContext($name): \Twig_Source
        {
            $name = $this->getMappedTemplateName($name);

            return $this->loader->getSourceContext($name);
        }
    }
} else {
    /**
     * Maps template names to their Sf 2.8 replacements.
     *
     * @internal
     */
    final class TemplateNameMappingLoader implements \Twig_LoaderInterface, \Twig_ExistsLoaderInterface
    {
        use TemplateNameMappingLoaderDecoratorTrait;
    }
}
