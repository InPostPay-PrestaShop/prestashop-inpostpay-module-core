<?php

declare(strict_types=1);

namespace izi\prestashop\DependencyInjection\Exception;

final class ContainerNotFoundException extends \RuntimeException
{
    public static function create(?\Exception $previous = null): self
    {
        return new self('DI container was not found.', 0, $previous);
    }
}
