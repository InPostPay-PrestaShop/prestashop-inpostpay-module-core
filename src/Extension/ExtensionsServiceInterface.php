<?php

declare(strict_types=1);

namespace izi\prestashop\Extension;

interface ExtensionsServiceInterface
{
    /**
     * @return Extension[]
     */
    public function getExtensions(): array;
}
