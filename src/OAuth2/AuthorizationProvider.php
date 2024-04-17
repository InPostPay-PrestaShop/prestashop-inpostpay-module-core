<?php

declare(strict_types=1);

namespace izi\prestashop\OAuth2;

use izi\prestashop\Clock\SystemClock;
use izi\prestashop\OAuth2\Authentication\ClientCredentialsInterface;
use izi\prestashop\OAuth2\Grant\GrantTypeInterface;
use izi\prestashop\OAuth2\Grant\RefreshTokenGrant;
use izi\prestashop\OAuth2\Token\AccessTokenFactoryInterface;
use izi\prestashop\OAuth2\Token\AccessTokenInterface;
use izi\prestashop\OAuth2\Token\AccessTokenRepositoryInterface;
use izi\prestashop\OAuth2\Token\BearerTokenFactory;
use izi\prestashop\OAuth2\Token\InMemoryTokenRepository;
use Psr\Clock\ClockInterface;
use Psr\Http\Message\ServerRequestInterface;

final class AuthorizationProvider implements AuthorizationProviderInterface
{
    /**
     * @var AuthorizationServerClientInterface
     */
    private $authServerClient;

    /**
     * @var GrantTypeInterface
     */
    private $grantType;

    /**
     * @var ClientCredentialsInterface
     */
    private $credentials;

    /**
     * @var AccessTokenRepositoryInterface
     */
    private $tokenRepository;

    /**
     * @var AccessTokenFactoryInterface
     */
    private $tokenFactory;

    /**
     * @var ClockInterface
     */
    private $clock;

    public function __construct(AuthorizationServerClientInterface $authServerClient, GrantTypeInterface $grantType, ClientCredentialsInterface $credentials, ?AccessTokenRepositoryInterface $tokenRepository = null, ?AccessTokenFactoryInterface $tokenFactory = null, ?ClockInterface $clock = null)
    {
        $this->authServerClient = $authServerClient;
        $this->grantType = $grantType;
        $this->credentials = $credentials;
        $this->tokenRepository = $tokenRepository ?? new InMemoryTokenRepository();
        $this->tokenFactory = $tokenFactory ?? new BearerTokenFactory();
        $this->clock = $clock ?? SystemClock::fromSystemTimezone();
    }

    /**
     * {@inheritDoc}
     */
    public function authorize(array $scopes = [])
    {
        $this->grantType->authorize($this->authServerClient, $this->credentials, $scopes);
    }

    /**
     * {@inheritDoc}
     */
    public function processAuthorizationResponse(ServerRequestInterface $request): void
    {
        $this->grantType->processAuthorizationResponse($request);
    }

    /**
     * {@inheritDoc}
     */
    public function getAccessToken(bool $renew = false, array $scopes = []): AccessTokenInterface
    {
        if (!$renew && $token = $this->getOrRefreshStoredToken($scopes)) {
            return $token;
        }

        $data = $this->grantType->getAccessToken($this->authServerClient, $this->credentials, $scopes);

        return $this->createAndSaveToken($data);
    }

    private function getOrRefreshStoredToken(array $scopes): ?AccessTokenInterface
    {
        if (!$token = $this->tokenRepository->getToken()) {
            return null;
        }

        if (!$this->hasRequestedScopes($token, $scopes)) {
            return null;
        }

        if (!$this->isExpired($token)) {
            return $token;
        }

        if (!$refreshToken = $token->getRefreshToken()) {
            return null;
        }

        return $this->refreshAccessToken($refreshToken);
    }

    private function hasRequestedScopes(AccessTokenInterface $token, array $scopes): bool
    {
        if ([] === $scopes) {
            return true;
        }

        if (!$tokenScopes = $token->getScopes()) {
            return false;
        }

        return [] === array_diff($scopes, $tokenScopes);
    }

    private function isExpired(AccessTokenInterface $token): bool
    {
        $expiresAt = $token->getExpiresAt();

        return null !== $expiresAt && $expiresAt <= $this->clock->now();
    }

    private function refreshAccessToken(string $refreshToken): AccessTokenInterface
    {
        $grant = new RefreshTokenGrant($refreshToken);
        $data = $grant->getAccessToken($this->authServerClient, $this->credentials);

        return $this->createAndSaveToken($data);
    }

    private function createAndSaveToken(array $data): AccessTokenInterface
    {
        $token = $this->tokenFactory->createToken($data);
        $this->tokenRepository->saveToken($token);

        return $token;
    }
}
