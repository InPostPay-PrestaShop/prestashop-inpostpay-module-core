<?php

declare(strict_types=1);

namespace izi\prestashop\Http\Client;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Client\NetworkExceptionInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

final class LoggingClient implements ClientInterface
{
    /**
     * @var ClientInterface
     */
    private $client;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(ClientInterface $client, LoggerInterface $logger)
    {
        $this->client = $client;
        $this->logger = $logger;
    }

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $this->logRequest($request);

        try {
            $response = $this->client->sendRequest($request);
            $this->logResponse($request, $response);

            return $response;
        } catch (\Throwable $throwable) {
            $this->logError($request, $throwable);

            throw $throwable;
        }
    }

    private function logRequest(RequestInterface $request): void
    {
        $this->logger->info('Request: "{method} {uri}"', [
            'method' => $request->getMethod(),
            'uri' => (string) $request->getUri(),
        ]);

        if ('' !== $body = (string) $request->getBody()) {
            $this->logger->debug('Request body: "{body}"', ['body' => $body]);
        }
    }

    private function logResponse(RequestInterface $request, ResponseInterface $response): void
    {
        $context = [
            'status_code' => $statusCode = $response->getStatusCode(),
            'uri' => (string) $request->getUri(),
        ];

        if (400 <= $statusCode) {
            if ('' !== $body = (string) $response->getBody()) {
                $context['body'] = $body;
            }

            $this->logger->error('Response: "{status_code} {uri}"', $context);
        } elseif (300 <= $statusCode) {
            $context['location'] = $response->getHeaderLine('location');
            $this->logger->info('Response: "{status_code} {uri}"', $context);
        } else {
            $this->logger->info('Response: "{status_code} {uri}"', $context);
            if ('' !== $body = (string) $response->getBody()) {
                $this->logger->debug('Response body: "{body}"', ['body' => $body]);
            }
        }
    }

    private function logError(RequestInterface $request, \Throwable $throwable): void
    {
        if ($throwable instanceof NetworkExceptionInterface) {
            $this->logger->error('Network error for {uri}: "{message}"', [
                'uri' => (string) $throwable->getRequest()->getUri(),
                'message' => $throwable->getMessage(),
            ]);
        } else {
            $this->logger->critical('Unexpected error for {uri}: {error}', [
                'uri' => (string) $request->getUri(),
                'error' => $throwable,
            ]);
        }
    }
}
