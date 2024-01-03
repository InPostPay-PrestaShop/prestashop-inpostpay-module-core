<?php

declare(strict_types=1);

namespace izi\prestashop\OAuth2;

use izi\prestashop\OAuth2\Token\AccessTokenInterface;
use Psr\Http\Message\ServerRequestInterface;

interface AuthorizationProviderInterface
{
    /**
     * @param string[] $scopes scopes to request
     */
    public function authorize(array $scopes = []);

    /**
     * Retrieves authorization response data for later usage by @see self::getAccessToken()
     */
    public function processAuthorizationResponse(ServerRequestInterface $request): void;

    /**
     * @param string[] $scopes scopes to request
     */
    public function getAccessToken(bool $renew = false, array $scopes = []): AccessTokenInterface;
}
