<?php

declare(strict_types=1);

namespace izi\prestashop\BasketApp;

use izi\prestashop\Configuration\ApiConfigurationInterface;
use izi\prestashop\Environment\AuthServerUriCollection;
use izi\prestashop\Http\Client\AuthorizingClient;
use izi\prestashop\Http\Client\Factory\ClientFactoryInterface;
use izi\prestashop\OAuth2\AuthorizationProviderFactoryInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Symfony\Component\Serializer\SerializerInterface;

final class BasketAppClientFactory
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
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var ClientFactoryInterface
     */
    private $clientFactory;

    /**
     * @var AuthorizationProviderFactoryInterface
     */
    private $authorizationProviderFactory;

    public function __construct(RequestFactoryInterface $requestFactory, StreamFactoryInterface $streamFactory, SerializerInterface $serializer, ClientFactoryInterface $clientFactory, ?AuthorizationProviderFactoryInterface $authorizationProviderFactory = null)
    {
        $this->requestFactory = $requestFactory;
        $this->streamFactory = $streamFactory;
        $this->serializer = $serializer;
        $this->clientFactory = $clientFactory;
        $this->authorizationProviderFactory = $authorizationProviderFactory ?? new AuthorizationProviderFactory($requestFactory, $streamFactory, $this->clientFactory);
    }

    public function create(ApiConfigurationInterface $configuration): BasketAppClient
    {
        $httpClient = $this->createHttpClient($configuration);
        $baseUri = $configuration->getEnvironment()->getBasketAppApiUri();

        return new BasketAppClient($httpClient, $this->requestFactory, $this->streamFactory, $this->serializer, $baseUri);
    }

    private function createHttpClient(ApiConfigurationInterface $configuration): ClientInterface
    {
        if (null === $credentials = $configuration->getClientCredentials()) {
            throw new \RuntimeException('Client credentials are not available.');
        }

        $uriCollection = new AuthServerUriCollection($configuration->getEnvironment());
        $authorizationProvider = $this->authorizationProviderFactory->create($uriCollection, $credentials);
        $client = $this->clientFactory->create();

        return new AuthorizingClient($client, $authorizationProvider);
    }
}
