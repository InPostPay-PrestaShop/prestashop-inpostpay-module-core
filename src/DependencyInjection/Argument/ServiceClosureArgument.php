<?php

declare(strict_types=1);

namespace izi\prestashop\DependencyInjection\Argument;

use izi\prestashop\DependencyInjection\Dumper\PhpDumper;
use Symfony\Component\DependencyInjection\Parameter;
use Symfony\Component\DependencyInjection\Reference;

/**
 * @see PhpDumper
 */
class ServiceClosureArgument extends Parameter
{
    private $value;

    public function __construct(Reference $reference)
    {
        $this->value = $reference;
    }

    public function getValue(): Reference
    {
        return $this->value;
    }
}
