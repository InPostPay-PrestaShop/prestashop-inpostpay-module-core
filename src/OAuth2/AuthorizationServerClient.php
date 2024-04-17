<?php

declare(strict_types=1);

namespace izi\prestashop\OAuth2;

use izi\prestashop\OAuth2\Authentication\AuthenticationMethodInterface;
use izi\prestashop\OAuth2\Authentication\ClientCredentialsInterface;
use izi\prestashop\OAuth2\Authentication\None;
use izi\prestashop\OAuth2\Exception\AccessTokenRequestException;
use izi\prestashop\OAuth2\Exception\NetworkException;
use izi\prestashop\OAuth2\Exception\UnexpectedValueException;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Client\NetworkExceptionInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

final class AuthorizationServerClient implements AuthorizationServerClientInterface
{
    /**
     * @var ClientInterface
     */
    private $client;

    /**
     * @var RequestFactoryInterface
     */
    private $requestFactory;

    /**
     * @var StreamFactoryInterface
     */
    private $streamFactory;

    /**
     * @var UriCollectionInterface
     */
    private $uriCollection;

    /**
     * @var AuthenticationMethodInterface
     */
    private $authenticationMethod;

    public function __construct(ClientInterface $client, RequestFactoryInterface $requestFactory, StreamFactoryInterface $streamFactory, UriCollectionInterface $uriCollection, ?AuthenticationMethodInterface $authenticationMethod = null)
    {
        $this->client = $client;
        $this->requestFactory = $requestFactory;
        $this->streamFactory = $streamFactory;
        $this->uriCollection = $uriCollection;
        $this->authenticationMethod = $authenticationMethod ?? new None();
    }

    /**
     * {@inheritDoc}
     */
    public function sendAccessTokenRequest(ClientCredentialsInterface $credentials, array $parameters): array
    {
        $request = $this->createAccessTokenRequest($credentials, $parameters);
        try {
            $response = $this->client->sendRequest($request);
        } catch (NetworkExceptionInterface $e) {
            throw new NetworkException($e);
        }

        if (200 !== $response->getStatusCode()) {
            $this->handleAccessTokenResponseError($response);
        }

        return $this->decodeAccessTokenResponse($response);
    }

    /**
     * {@inheritDoc}
     */
    public function redirectToAuthorizationEndpoint(array $parameters)
    {
        $url = $this->getAuthorizationUrl($parameters);

        header('Location: ' . $url);
        exit;
    }

    private function createAccessTokenRequest(ClientCredentialsInterface $credentials, array $parameters): RequestInterface
    {
        $request = $this->requestFactory->createRequest('POST', $this->uriCollection->getTokenEndpointUri());
        $request = $this->authenticationMethod->authenticate($request, $parameters, $credentials);

        $body = $this->streamFactory->createStream(http_build_query($parameters, '', '&'));

        return $request
            ->withBody($body)
            ->withHeader('Content-Type', 'application/x-www-form-urlencoded');
    }

    private function decodeAccessTokenResponse(ResponseInterface $response): array
    {
        $data = $this->decodeJsonResponse($response);

        if (!isset($data['token_type']) || !is_string($data['token_type'])) {
            throw new UnexpectedValueException($response, 'Token type is missing in the response data.');
        }

        if (!isset($data['access_token']) || !is_string($data['access_token'])) {
            throw new UnexpectedValueException($response, 'Access token is missing in the response data.');
        }

        return $data;
    }

    private function handleAccessTokenResponseError(ResponseInterface $response): void
    {
        try {
            $data = $this->decodeJsonResponse($response);
        } catch (\Exception $exception) {
            // decoding exception
        }

        if (isset($data['error'])) {
            throw AccessTokenRequestException::create($data, $response);
        }

        throw new UnexpectedValueException('Unexpected access token response format.', 0, $exception ?? null);
    }

    private function getAuthorizationUrl(array $parameters): string
    {
        $url = $this->uriCollection->getAuthorizationEndpointUri();
        $query = http_build_query($parameters, '', '&', PHP_QUERY_RFC3986);

        return false === strpos($url, '?')
            ? $url . '?' . $query
            : $url . '&' . $query;
    }

    private function decodeJsonResponse(ResponseInterface $response): array
    {
        if ('' === $content = (string) $response->getBody()) {
            throw new UnexpectedValueException('Response body is empty.');
        }

        $data = json_decode($content, true, 512, JSON_BIGINT_AS_STRING);

        if (null === $data && JSON_ERROR_NONE !== $errorCode = json_last_error()) {
            throw new UnexpectedValueException(json_last_error_msg(), $errorCode);
        }

        if (!is_array($data)) {
            throw new UnexpectedValueException(sprintf('JSON content was expected to decode to an array, "%s" returned".', gettype($data)));
        }

        return $data;
    }
}
