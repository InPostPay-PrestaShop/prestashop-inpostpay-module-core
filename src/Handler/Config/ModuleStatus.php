<?php

declare(strict_types=1);

namespace izi\prestashop\Handler\Config;

final class ModuleStatus
{
    /**
     * @var string[]
     */
    private $errors;

    public function __construct(string ...$errors)
    {
        $this->errors = $errors;
    }

    public function isOK(): bool
    {
        return [] === $this->errors;
    }

    /**
     * @return string[]
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
