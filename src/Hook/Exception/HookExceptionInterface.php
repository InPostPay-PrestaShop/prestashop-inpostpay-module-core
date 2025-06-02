<?php

declare(strict_types=1);

namespace izi\prestashop\Hook\Exception;

interface HookExceptionInterface
{
    public function getHookName(): string;
}
