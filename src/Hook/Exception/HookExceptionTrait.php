<?php

declare(strict_types=1);

namespace izi\prestashop\Hook\Exception;

/**
 * @mixin \Exception
 */
trait HookExceptionTrait
{
    private $hookName;

    public function __construct(string $hookName, string $message = '', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);

        $this->hookName = $hookName;
    }

    public function getHookName(): string
    {
        return $this->hookName;
    }
}
