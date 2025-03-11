<?php

declare(strict_types=1);

namespace izi\prestashop\Environment;

final class SandboxEnvironment implements EnvironmentInterface
{
    public function getType(): EnvironmentType
    {
        return EnvironmentType::Sandbox();
    }

    public function getBasketAppApiUri(): string
    {
        return 'https://sandbox-api.inpost.pl';
    }

    public function getAuthServerTokenEndpointUri(): string
    {
        return 'https://sandbox-login.inpost.pl/auth/realms/external/protocol/openid-connect/token';
    }

    public function getWidgetJavaScriptUri(): string
    {
        return 'https://sandbox-inpostpay-widget-v2.inpost.pl/inpostpay.widget.v2.js';
    }
}
