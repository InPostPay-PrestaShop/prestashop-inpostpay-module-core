<?php

declare(strict_types=1);

namespace izi\prestashop\Hook\Exception;

interface HookExceptionInterface
{
    /**
     * @deprecated
     */
    public function getHookName(): string;
}
