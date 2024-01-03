<?php

declare(strict_types=1);

namespace izi\prestashop\BasketApp;

use izi\prestashop\Http\Client\Factory\ClientFactoryInterface;
use izi\prestashop\Http\Client\Factory\GuzzleClientFactory;
use izi\prestashop\OAuth2\Authentication\ClientCredentialsInterface;
use izi\prestashop\OAuth2\Authentication\ClientSecretPost;
use izi\prestashop\OAuth2\AuthorizationProvider;
use izi\prestashop\OAuth2\AuthorizationProviderFactoryInterface;
use izi\prestashop\OAuth2\AuthorizationProviderInterface;
use izi\prestashop\OAuth2\AuthorizationServerClient;
use izi\prestashop\OAuth2\AuthorizationServerClientInterface;
use izi\prestashop\OAuth2\Grant\ClientCredentialsGrant;
use izi\prestashop\OAuth2\Token\AccessTokenRepositoryInterface;
use izi\prestashop\OAuth2\UriCollectionInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

final class AuthorizationProviderFactory implements AuthorizationProviderFactoryInterface
{
    /**
     * @var RequestFactoryInterface
     */
    private $requestFactory;

    /**
     * @var StreamFactoryInterface
     */
    private $streamFactory;

    /**
     * @var ClientFactoryInterface
     */
    private $clientFactory;

    public function __construct(RequestFactoryInterface $requestFactory, StreamFactoryInterface $streamFactory, ClientFactoryInterface $clientFactory = null)
    {
        $this->requestFactory = $requestFactory;
        $this->streamFactory = $streamFactory;
        $this->clientFactory = $clientFactory ?? new GuzzleClientFactory();
    }

    public function create(UriCollectionInterface $uriCollection, ClientCredentialsInterface $credentials, AccessTokenRepositoryInterface $tokenRepository = null): AuthorizationProviderInterface
    {
        $authSeverClient = $this->createAuthServerClient($uriCollection);

        return new AuthorizationProvider(
            $authSeverClient,
            new ClientCredentialsGrant(),
            $credentials,
            $tokenRepository
        );
    }

    private function createAuthServerClient(UriCollectionInterface $uriCollection): AuthorizationServerClientInterface
    {
        $httpClient = $this->clientFactory->create();

        return new AuthorizationServerClient(
            $httpClient,
            $this->requestFactory,
            $this->streamFactory,
            $uriCollection,
            new ClientSecretPost()
        );
    }
}
