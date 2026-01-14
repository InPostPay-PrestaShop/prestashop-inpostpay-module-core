<?php

declare(strict_types=1);

namespace izi\prestashop\Hook\Exception;

class HookNotImplementedException extends \LogicException implements HookExceptionInterface
{
    public static function create(string $hookName): self
    {
        return new self(\sprintf('Hook "%s" is not implemented.', $hookName));
    }
}
