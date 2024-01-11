<?php

declare(strict_types=1);

namespace izi\prestashop\DependencyInjection;

use izi\prestashop\DependencyInjection\Argument\ServiceClosureArgument;
use Symfony\Component\DependencyInjection\ContainerBuilder as BaseContainerBuilder;

final class ContainerBuilder extends BaseContainerBuilder
{
    private $compiled = false;

    public function resolveServices($value)
    {
        if ($value instanceof ServiceClosureArgument) {
            $reference = $value->getValue();

            return function () use ($reference) {
                return $this->resolveServices($reference);
            };
        }

        return parent::resolveServices($value);
    }

    public function compile(): void
    {
        parent::compile(...func_get_args());
        $this->compiled = true;
    }

    public function isCompiled(): bool
    {
        return $this->compiled;
    }
}
