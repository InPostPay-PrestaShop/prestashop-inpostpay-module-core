<?php

declare(strict_types=1);

namespace izi\prestashop\OAuth2;

use izi\prestashop\OAuth2\Authentication\ClientCredentialsRepositoryInterface;
use izi\prestashop\OAuth2\Token\AccessTokenInterface;
use izi\prestashop\OAuth2\Token\AccessTokenRepositoryInterface;
use Psr\Http\Message\ServerRequestInterface;

final class LazyAuthorizationProvider implements AuthorizationProviderInterface
{
    /**
     * @var AuthorizationProviderFactoryInterface
     */
    private $factory;

    /**
     * @var UriCollectionInterface
     */
    private $uriCollection;

    /**
     * @var ClientCredentialsRepositoryInterface
     */
    private $credentialsRepository;

    /**
     * @var AccessTokenRepositoryInterface|null
     */
    private $tokenRepository;

    /**
     * @var AuthorizationProviderInterface|null
     */
    private $authProvider;

    public function __construct(AuthorizationProviderFactoryInterface $factory, UriCollectionInterface $uriCollection, ClientCredentialsRepositoryInterface $credentialsRepository, ?AccessTokenRepositoryInterface $tokenRepository = null)
    {
        $this->factory = $factory;
        $this->uriCollection = $uriCollection;
        $this->credentialsRepository = $credentialsRepository;
        $this->tokenRepository = $tokenRepository;
    }

    public function authorize(array $scopes = [])
    {
        $this->getAuthProvider()->authorize($scopes);
    }

    public function processAuthorizationResponse(ServerRequestInterface $request): void
    {
        $this->getAuthProvider()->processAuthorizationResponse($request);
    }

    public function getAccessToken(bool $renew = false, array $scopes = []): AccessTokenInterface
    {
        return $this->getAuthProvider()->getAccessToken($renew, $scopes);
    }

    private function getAuthProvider(): AuthorizationProviderInterface
    {
        return $this->authProvider ?? ($this->authProvider = $this->createAuthProvider());
    }

    private function createAuthProvider(): AuthorizationProviderInterface
    {
        if (null === $credentials = $this->credentialsRepository->getClientCredentials()) {
            throw new \RuntimeException('Client credentials are not available.');
        }

        return $this->factory->create($this->uriCollection, $credentials, $this->tokenRepository);
    }
}
