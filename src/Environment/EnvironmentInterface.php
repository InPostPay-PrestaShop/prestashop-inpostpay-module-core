<?php

declare(strict_types=1);

namespace izi\prestashop\Environment;

interface EnvironmentInterface
{
    public function getBasketAppApiUri(): string;

    public function getAuthServerTokenEndpointUri(): string;

    public function getWidgetJavaScriptUri(): string;

    public function getDeepLinkUri(): string;
}
