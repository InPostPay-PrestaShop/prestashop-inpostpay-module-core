<?php

declare(strict_types=1);

namespace izi\prestashop\Extension;

use izi\prestashop\Http\Exception\ClientException;
use izi\prestashop\Http\Exception\RedirectionException;
use izi\prestashop\Http\Exception\ServerException;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Serializer\SerializerInterface;

final class ExtensionsService implements ExtensionsServiceInterface
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
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var string
     */
    private $url;

    public function __construct(ClientInterface $client, RequestFactoryInterface $requestFactory, SerializerInterface $serializer, string $url)
    {
        $this->client = $client;
        $this->requestFactory = $requestFactory;
        $this->serializer = $serializer;
        $this->url = $url;
    }

    public function getExtensions(): array
    {
        $request = $this->requestFactory->createRequest('GET', $this->url);
        $response = $this->client->sendRequest($request);

        if (200 !== $response->getStatusCode()) {
            $this->handleUnsuccessfulResponse($request, $response);
        }

        return $this->serializer->deserialize((string) $response->getBody(), Extension::class . '[]', 'json');
    }

    private function handleUnsuccessfulResponse(RequestInterface $request, ResponseInterface $response): void
    {
        $statusCode = $response->getStatusCode();

        if (500 <= $statusCode) {
            throw new ServerException($request, $response);
        }

        if (400 <= $statusCode) {
            throw new ClientException($request, $response);
        }

        throw new RedirectionException($request, $response);
    }
}
