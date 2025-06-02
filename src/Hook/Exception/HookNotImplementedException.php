<?php

declare(strict_types=1);

namespace izi\prestashop\Hook\Exception;

class HookNotImplementedException extends \LogicException implements HookExceptionInterface
{
    use HookExceptionTrait;

    public static function create(string $hookName): self
    {
        return new self($hookName, sprintf('Hook "%s" is not implemented.', $hookName));
    }
}
