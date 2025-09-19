<?php

declare(strict_types=1);

namespace izi\prestashop\Extension;

use izi\prestashop\Extension\Exception\HttpException;
use izi\prestashop\Extension\Exception\NetworkException;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Client\NetworkExceptionInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @phpstan-type Options array{url: string, max_redirects?: int}
 */
final class ExtensionsService implements ExtensionsServiceInterface
{
    private const DEFAULT_OPTIONS = [
        'max_redirects' => 0,
    ];

    /**
     * @var ClientInterface
     */
    private $client;

    /**
     * @var RequestFactoryInterface
     */
    private $requestFactory;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var Options
     */
    private $options;

    /**
     * @param Options $options
     */
    public function __construct(ClientInterface $client, RequestFactoryInterface $requestFactory, SerializerInterface $serializer, array $options)
    {
        if (!isset($options['url'])) {
            throw new \InvalidArgumentException('Option "url" is required.');
        }

        $this->client = $client;
        $this->requestFactory = $requestFactory;
        $this->serializer = $serializer;
        $this->options = array_merge(self::DEFAULT_OPTIONS, $options);
    }

    public function getExtensions(): array
    {
        $response = $this->sendRequest($this->options['url']);

        return $this->serializer->deserialize((string) $response->getBody(), Extension::class . '[]', 'json');
    }

    private function sendRequest(string $url, int $redirects = 0): ResponseInterface
    {
        $request = $this->requestFactory->createRequest('GET', $url);

        try {
            $response = $this->client->sendRequest($request);
        } catch (NetworkExceptionInterface $e) {
            throw new NetworkException($e);
        }

        if (200 === $statusCode = $response->getStatusCode()) {
            return $response;
        }

        if ($statusCode >= 300 && $statusCode < 400 && $redirects < $this->options['max_redirects']) {
            return $this->sendRequest($response->getHeaderLine('Location'), $redirects + 1);
        }

        throw new HttpException($request, $response);
    }
}
