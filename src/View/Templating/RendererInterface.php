<?php

declare(strict_types=1);

namespace izi\prestashop\View\Templating;

interface RendererInterface
{
    public function render(string $name, array $parameters = []): string;
}
