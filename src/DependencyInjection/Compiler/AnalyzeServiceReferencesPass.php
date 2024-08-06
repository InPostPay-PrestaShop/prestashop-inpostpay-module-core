<?php

declare(strict_types=1);

namespace izi\prestashop\DependencyInjection\Compiler;

use izi\prestashop\DependencyInjection\Argument\ServiceClosureArgument;
use Symfony\Component\DependencyInjection\Alias;
use Symfony\Component\DependencyInjection\Compiler\AnalyzeServiceReferencesPass as DecoratedPass;
use Symfony\Component\DependencyInjection\Compiler\RepeatablePassInterface;
use Symfony\Component\DependencyInjection\Compiler\RepeatedPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class AnalyzeServiceReferencesPass implements RepeatablePassInterface
{
    private $pass;
    private $locatorTag;

    public function __construct(RepeatablePassInterface $pass, string $locatorTag)
    {
        $this->pass = $pass;
        $this->locatorTag = $locatorTag;
    }

    public static function decorateRemovingPasses(ContainerBuilder $container, string $locatorTag): void
    {
        $passConfig = $container->getCompilerPassConfig();
        $removingPasses = $passConfig->getRemovingPasses();

        foreach ($removingPasses as $key => $pass) {
            if (!$pass instanceof RepeatedPass) {
                continue;
            }

            $removingPasses[$key] = new RepeatedPass(array_map(static function (RepeatablePassInterface $pass) use ($locatorTag) {
                if (!$pass instanceof DecoratedPass) {
                    return $pass;
                }

                return new AnalyzeServiceReferencesPass($pass, $locatorTag);
            }, $pass->getPasses()));
        }

        $passConfig->setRemovingPasses($removingPasses);
    }

    public function setRepeatedPass(RepeatedPass $repeatedPass): void
    {
        $this->pass->setRepeatedPass($repeatedPass);
    }

    public function process(ContainerBuilder $container): void
    {
        $this->pass->process($container);
        $graph = $container->getCompiler()->getServiceReferenceGraph();

        $aliases = array_map(static function (Alias $alias) {
            return (string) $alias;
        }, $container->getAliases());

        foreach ($container->findTaggedServiceIds($this->locatorTag) as $locatorId => $tags) {
            $definition = $container->getDefinition($locatorId);

            /** @var ServiceClosureArgument $value */
            foreach ($definition->getArgument(0) as $value) {
                $id = (string) $value->getValue();
                $id = $aliases[$id] ?? $id;

                $graph->connect(
                    $locatorId,
                    $definition,
                    $id,
                    $container->getDefinition($id)
                );
            }
        }
    }
}
