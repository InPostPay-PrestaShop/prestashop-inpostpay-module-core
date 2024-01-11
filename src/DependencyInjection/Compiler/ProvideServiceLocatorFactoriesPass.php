<?php

namespace izi\prestashop\DependencyInjection\Compiler;

use izi\prestashop\DependencyInjection\Argument\ServiceClosureArgument;
use izi\prestashop\DependencyInjection\TypedReference;
use Symfony\Component\DependencyInjection\Alias;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Exception\RuntimeException;
use Symfony\Component\DependencyInjection\Reference;

final class ProvideServiceLocatorFactoriesPass implements CompilerPassInterface
{
    /**
     * @var string
     */
    private $locatorTag;

    public function __construct(string $locatorTag)
    {
        $this->locatorTag = $locatorTag;
    }

    public function process(ContainerBuilder $container): void
    {
        foreach ($container->findTaggedServiceIds($this->locatorTag) as $id => $tags) {
            $definition = $container->getDefinition($id);
            $argument = $this->prepareFactories($container, $id, $tags);
            $definition->addArgument($argument);
        }
    }

    /**
     * @return array<string, ServiceClosureArgument>
     */
    private function prepareFactories(ContainerBuilder $container, string $id, array $tags): array
    {
        $aliases = array_flip(array_map(static function (Alias $alias) {
            return (string) $alias;
        }, $container->getAliases()));

        $refMaps = [];

        foreach ($tags as $attributes) {
            if (!isset($attributes['tag'])) {
                throw new InvalidArgumentException(sprintf('Service locator "%s" definition is invalid: "tag" attribute is missing.', $id));
            }

            $refMaps[] = $this->processTag($container, $attributes);
        }

        $refMap = array_merge(...$refMaps);
        ksort($refMap);

        return array_map(static function (Reference $reference) use ($container, $aliases) {
            $id = (string) $reference;
            $definition = $container->getDefinition($id);
            $reference = isset($aliases[$id]) ? new Reference($aliases[$id]) : $reference;

            if (null !== $class = $definition->getClass()) {
                $reference = new TypedReference((string) $reference, $class);
            }

            return new ServiceClosureArgument($reference);
        }, $refMap);
    }

    private function processTag(ContainerBuilder $container, array $attributes): array
    {
        $refMap = [];

        foreach ($container->findTaggedServiceIds($attributes['tag']) as $id => $tags) {
            foreach ($tags as $tag) {
                $locatorId = $this->resolveServiceId($container, $id, $attributes, $tag);
                $refMap[$locatorId] = new Reference($id);
            }
        }

        return $refMap;
    }

    private function resolveServiceId(ContainerBuilder $container, string $id, array $attributes, array $tag): string
    {
        if (!isset($attributes['index_by'])) {
            return $id;
        }

        $indexBy = $attributes['index_by'];

        if (isset($tag[$indexBy])) {
            return $tag[$indexBy];
        }

        if (!isset($attributes['default_index_method'])) {
            return $id;
        }

        $method = $attributes['default_index_method'];

        if (null === $class = $container->getDefinition($id)->getClass()) {
            throw new RuntimeException(sprintf('The definition for "%s" has no class.', $id));
        }

        if (!is_callable([$class, $method])) {
            throw new RuntimeException(sprintf('%s::%s is not callable.', $class, $method));
        }

        return $class::$method();
    }
}
