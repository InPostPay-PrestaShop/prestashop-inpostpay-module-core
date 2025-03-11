<?php

declare(strict_types=1);

namespace izi\prestashop\Environment;

interface EnvironmentInterface
{
    public function getType(): EnvironmentType;

    public function getBasketAppApiUri(): string;

    public function getAuthServerTokenEndpointUri(): string;

    public function getWidgetJavaScriptUri(): string;
}
