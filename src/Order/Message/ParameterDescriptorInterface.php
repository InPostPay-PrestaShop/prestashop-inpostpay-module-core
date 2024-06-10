<?php

declare(strict_types=1);

namespace izi\prestashop\Order\Message;

interface ParameterDescriptorInterface
{
    /**
     * @return array<string, string> description by parameter name
     */
    public function getDescriptions(): array;
}
