<?php

declare(strict_types=1);

namespace izi\prestashop\OAuth2\Grant;

use izi\prestashop\OAuth2\Authentication\ClientCredentialsInterface;
use izi\prestashop\OAuth2\AuthorizationServerClientInterface;
use Psr\Http\Message\ServerRequestInterface;

abstract class AbstractGrant implements GrantTypeInterface
{
    /**
     * {@inheritDoc}
     */
    public function authorize(AuthorizationServerClientInterface $authServerClient, ClientCredentialsInterface $credentials, array $scopes = [])
    {
    }

    /**
     * {@inheritDoc}
     */
    public function processAuthorizationResponse(ServerRequestInterface $request): void
    {
    }

    /**
     * {@inheritDoc}
     */
    public function getAccessToken(AuthorizationServerClientInterface $authServerClient, ClientCredentialsInterface $credentials, array $scopes = []): array
    {
        $params = $this->getAccessTokenRequestParameters($credentials, $scopes);

        return $authServerClient->sendAccessTokenRequest($credentials, $params);
    }

    protected function getAccessTokenRequestParameters(ClientCredentialsInterface $credentials, array $scopes): array
    {
        $params = ['grant_type' => $this->getIdentifier()];
        if ([] !== $scopes) {
            $params['scope'] = implode(' ', $scopes);
        }

        return $params;
    }
}
