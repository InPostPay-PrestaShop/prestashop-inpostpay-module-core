<?php

declare(strict_types=1);

namespace izi\prestashop\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

final class TaggedIteratorsCollectorPass implements CompilerPassInterface
{
    public function __construct(?string $serviceId = null)
    {
        if (null !== $serviceId) {
            @trigger_error(sprintf('Passing $serviceId to %s::__construct() is deprecated. Currently all iterators are collected in a single pass.', __CLASS__), E_USER_DEPRECATED);
        }
    }

    public function process(ContainerBuilder $container): void
    {
        foreach ($container->getDefinitions() as $definition) {
            $this->processDefinition($definition, $container);
        }
    }

    private function processDefinition(Definition $definition, ContainerBuilder $container): void
    {
        foreach ($definition->getArguments() as $index => $argument) {
            if (!is_string($argument)) {
                continue;
            }

            if (!preg_match('/^!tagged (.*)$/', $argument, $matches)) {
                continue;
            }

            $tagName = $matches[1];
            $value = $this->findAndSortTaggedServices($tagName, $container);

            $definition->replaceArgument($index, $value);
        }
    }

    private function findAndSortTaggedServices(string $tagName, ContainerBuilder $container): array
    {
        $services = [];

        foreach ($container->findTaggedServiceIds($tagName, true) as $serviceId => $attributes) {
            $priority = $attributes[0]['priority'] ?? 0;
            $services[$priority][] = new Reference($serviceId);
        }

        if ($services) {
            krsort($services);
            $services = array_merge(...$services);
        }

        return $services;
    }
}
