<?php

declare(strict_types=1);

namespace izi\prestashop\Http\Client\Adapter;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\ClientInterface as GuzzleClientInterface;
use GuzzleHttp\Exception as GuzzleExceptions;
use GuzzleHttp\Message\RequestInterface as GuzzleRequest;
use GuzzleHttp\Message\ResponseInterface as GuzzleResponse;
use Nyholm\Psr7\Response;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

final class Guzzle5Adapter implements ClientInterface
{
    private $client;

    public function __construct(GuzzleClientInterface $client = null)
    {
        $this->client = $client ?: new GuzzleClient();
    }

    /**
     * {@inheritDoc}
     */
    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $guzzleRequest = $this->createRequest($request);

        try {
            $response = $this->client->send($guzzleRequest);
        } catch (GuzzleExceptions\TransferException $exception) {
            if (!$exception instanceof GuzzleExceptions\RequestException || !$exception->hasResponse()) {
                throw $this->handleException($exception, $request);
            }

            $response = $exception->getResponse();
        }

        return $this->createResponse($response);
    }

    private function createRequest(RequestInterface $request): GuzzleRequest
    {
        $body = (string) $request->getBody();

        return $this->client->createRequest($request->getMethod(), (string) $request->getUri(), [
            'exceptions' => false,
            'allow_redirects' => false,
            'version' => $request->getProtocolVersion(),
            'headers' => $request->getHeaders(),
            'body' => '' === $body ? null : $body,
        ]);
    }

    private function createResponse(GuzzleResponse $response): ResponseInterface
    {
        $body = $response->getBody();

        return new Response(
            $response->getStatusCode(),
            $response->getHeaders(),
            isset($body) ? $body->detach() : null,
            $response->getProtocolVersion(),
            $response->getReasonPhrase()
        );
    }

    private function handleException(GuzzleExceptions\TransferException $exception, RequestInterface $request): ClientExceptionInterface
    {
        return $exception instanceof GuzzleExceptions\ConnectException
            ? NetworkException::fromGuzzleException($exception, $request)
            : RequestException::fromGuzzleException($exception, $request);
    }
}
