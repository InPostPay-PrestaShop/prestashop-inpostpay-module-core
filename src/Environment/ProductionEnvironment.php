<?php

declare(strict_types=1);

namespace izi\prestashop\Environment;

final class ProductionEnvironment implements EnvironmentInterface
{
    public function getType(): EnvironmentType
    {
        return EnvironmentType::Production();
    }

    public function getBasketAppApiUri(): string
    {
        return 'https://api.inpost.pl';
    }

    public function getAuthServerTokenEndpointUri(): string
    {
        return 'https://login.inpost.pl/auth/realms/external/protocol/openid-connect/token';
    }

    public function getWidgetJavaScriptUri(): string
    {
        return 'https://inpostpay-widget-v2.inpost.pl/inpostpay.widget.v2.js';
    }
}
