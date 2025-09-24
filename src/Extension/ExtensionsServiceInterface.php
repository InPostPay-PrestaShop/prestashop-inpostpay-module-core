<?php

declare(strict_types=1);

namespace izi\prestashop\Extension;

use izi\prestashop\Extension\Exception\ExtensionServiceException;

interface ExtensionsServiceInterface
{
    /**
     * @return Extension[]
     *
     * @throws ExtensionServiceException if extension data could not be fetched
     */
    public function getExtensions(): array;
}
