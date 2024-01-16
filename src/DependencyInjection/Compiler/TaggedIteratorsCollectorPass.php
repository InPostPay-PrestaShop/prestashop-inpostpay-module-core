<?php

declare(strict_types=1);

namespace izi\prestashop\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

final class TaggedIteratorsCollectorPass implements CompilerPassInterface
{
    /**
     * @var string
     */
    private $serviceId;

    /**
     * @param string $serviceId
     */
    public function __construct(string $serviceId)
    {
        $this->serviceId = $serviceId;
    }

    public function process(ContainerBuilder $container): void
    {
        if (!$container->has($this->serviceId)) {
            return;
        }

        $definition = $container->findDefinition($this->serviceId);

        foreach ($definition->getArguments() as $index => $argument) {
            if (!is_string($argument)) {
                continue;
            }

            if (!preg_match('/^!tagged (.*)$/', $argument, $matches)) {
                continue;
            }

            $tagName = $matches[1];

            $value = [];
            foreach ($container->findTaggedServiceIds($tagName) as $id => $tags) {
                $value[] = new Reference($id);
            }

            $definition->replaceArgument($index, $value);
        }
    }
}
