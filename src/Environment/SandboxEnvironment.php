<?php

declare(strict_types=1);

namespace izi\prestashop\Environment;

final class SandboxEnvironment implements EnvironmentInterface
{
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
        return 'https://izi-sandbox.inpost.pl/inpostizi.js';
    }

    public function getDeepLinkUri(): string
    {
        return 'inpost://izilinksandbox';
    }
}
