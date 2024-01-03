<?php

declare(strict_types=1);

namespace izi\prestashop\OAuth2;

use izi\prestashop\OAuth2\Authentication\ClientCredentialsInterface;

interface AuthorizationServerClientInterface
{
    /**
     * @return array decoded access token response
     */
    public function sendAccessTokenRequest(ClientCredentialsInterface $credentials, array $parameters): array;

    /**
     * @return never-return
     */
    public function redirectToAuthorizationEndpoint(array $parameters);
}
