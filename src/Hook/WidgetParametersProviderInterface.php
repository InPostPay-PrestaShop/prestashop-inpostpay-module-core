<?php

declare(strict_types=1);

namespace izi\prestashop\Hook;

interface WidgetParametersProviderInterface
{
    public function getParameters(?string $hookName, array $parameters): array;
}
