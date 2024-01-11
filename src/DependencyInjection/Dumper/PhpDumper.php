<?php

declare(strict_types=1);

namespace izi\prestashop\DependencyInjection\Dumper;

use izi\prestashop\DependencyInjection\Argument\ServiceClosureArgument;
use izi\prestashop\DependencyInjection\TypedReference;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Dumper\PhpDumper as BaseDumper;

/**
 * Dumps service closures on Sf 2.8. Might be an ugly hack, but it works...
 *
 * @internal
 */
final class PhpDumper extends BaseDumper
{
    private $parentMethod;

    public function dumpParameter($value): string
    {
        if (!$value instanceof ServiceClosureArgument) {
            return parent::dumpParameter($value);
        }

        $reference = $value->getValue();
        $code = $this->dumpValue($reference);

        $returnedType = '';
        if ($reference instanceof TypedReference) {
            $returnedType = sprintf(': %s\%s', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE >= $reference->getInvalidBehavior() ? '' : '?', $reference->getType());
        }

        $code = sprintf('return %s;', $code);

        return sprintf("function ()%s {\n            %s\n        }", $returnedType, $code);
    }

    protected function dumpValue($value, bool $interpolate = true): string
    {
        if (!isset($this->parentMethod)) {
            $this->parentMethod = \Closure::bind(function ($value, $interpolate) {
                return $this->dumpValue($value, $interpolate);
            }, $this, BaseDumper::class);
        }

        return ($this->parentMethod)($value, $interpolate);
    }
}
