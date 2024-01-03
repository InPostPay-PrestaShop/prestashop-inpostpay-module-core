<?php

declare(strict_types=1);

namespace izi\prestashop\OAuth2\Grant;

use izi\prestashop\OAuth2\Authentication\ClientCredentialsInterface;

/**
 * @internal
 */
final class RefreshTokenGrant extends AbstractGrant
{
    public const IDENTIFIER = 'refresh_token';

    /**
     * @var string
     */
    private $refreshToken;

    public function __construct(string $refreshToken)
    {
        $this->refreshToken = $refreshToken;
    }

    public function getIdentifier(): string
    {
        return self::IDENTIFIER;
    }

    protected function getAccessTokenRequestParameters(ClientCredentialsInterface $credentials, array $scopes): array
    {
        $params = parent::getAccessTokenRequestParameters($credentials, $scopes);
        $params['refresh_token'] = $this->refreshToken;

        return $params;
    }
}
