<?php

declare(strict_types=1);

namespace izi\prestashop\Http\Client;

use izi\prestashop\OAuth2\AuthorizationProviderInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

final class AuthorizingClient implements ClientInterface
{
    /**
     * @var ClientInterface
     */
    private $client;

    /**
     * @var AuthorizationProviderInterface
     */
    private $authorizationProvider;

    /**
     * @var string[]
     */
    private $scopes;

    /**
     * @param string[] $scopes scopes to request from the authorization server
     */
    public function __construct(ClientInterface $client, AuthorizationProviderInterface $authorizationProvider, array $scopes = [])
    {
        $this->client = $client;
        $this->authorizationProvider = $authorizationProvider;
        $this->scopes = $scopes;
    }

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $request = $this->authorize($request);
        $response = $this->client->sendRequest($request);

        if (401 !== $response->getStatusCode()) {
            return $response;
        }

        $request = $this->authorize($request, true);

        return $this->client->sendRequest($request);
    }

    private function authorize(RequestInterface $request, bool $renewAccessToken = false): RequestInterface
    {
        return $this->authorizationProvider
            ->getAccessToken($renewAccessToken, $this->scopes)
            ->authorize($request);
    }
}
