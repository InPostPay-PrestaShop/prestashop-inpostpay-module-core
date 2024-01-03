<?php

declare(strict_types=1);

namespace izi\prestashop\Environment;

final class UatEnvironment implements EnvironmentInterface
{
    public function getBasketAppApiUri(): string
    {
        return 'https://uat-api.inpost.pl';
    }

    public function getAuthServerTokenEndpointUri(): string
    {
        return 'https://uat-auth.easypack24.net/auth/realms/external/protocol/openid-connect/token';
    }

    public function getWidgetJavaScriptUri(): string
    {
        return 'https://izi-uat.inpost.pl/inpostizi.js';
    }

    public function getDeepLinkUri(): string
    {
        return 'inpost://izilinkuat';
    }
}
