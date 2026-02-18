<?php

declare(strict_types=1);

namespace izi\prestashop\DependencyInjection\Compiler;

use Symfony\Component\Config\Resource\ReflectionClassResource;
use Symfony\Component\Config\Resource\ResourceInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Prevents tracking changes in classes with circular references in static properties.
 *
 * @see https://github.com/symfony/symfony/pull/32809
 *
 * @internal
 */
final class FilterResourcesPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $resources = array_filter($container->getResources(), static function (ResourceInterface $resource) {
            if (!$resource instanceof ReflectionClassResource) {
                return true;
            }

            $class = explode('.', (string) $resource, 2)[1];

            if (\in_array($class, [\Context::class, \Db::class], true)) {
                return false;
            }

            return !is_subclass_of($class, \ModuleCore::class);
        });

        $container->setResources($resources);
    }
}
