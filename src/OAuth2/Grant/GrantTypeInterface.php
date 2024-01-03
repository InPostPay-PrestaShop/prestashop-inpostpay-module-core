<?php

declare(strict_types=1);

namespace izi\prestashop\OAuth2\Grant;

use izi\prestashop\OAuth2\Authentication\ClientCredentialsInterface;
use izi\prestashop\OAuth2\AuthorizationServerClientInterface;
use Psr\Http\Message\ServerRequestInterface;

interface GrantTypeInterface
{
    public function getIdentifier(): string;

    /**
     * @param string[] $scopes requested scopes
     */
    public function authorize(AuthorizationServerClientInterface $authServerClient, ClientCredentialsInterface $credentials, array $scopes = []);

    /**
     * Retrieves authorization response data for later usage by @see self::getAccessToken()
     */
    public function processAuthorizationResponse(ServerRequestInterface $request): void;

    /**
     * @param string[] $scopes requested scopes
     *
     * @return array decoded access token response data
     */
    public function getAccessToken(AuthorizationServerClientInterface $authServerClient, ClientCredentialsInterface $credentials, array $scopes = []): array;
}
