<?php

declare(strict_types=1);

namespace izi\prestashop\Product\Description;

interface DescriptionSanitizerInterface
{
    /**
     * @throws \RuntimeException
     */
    public function sanitize(string $html): string;
}
