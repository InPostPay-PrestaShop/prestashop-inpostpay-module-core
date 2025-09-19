<?php

declare(strict_types=1);

namespace izi\prestashop\Extension;

use izi\prestashop\Extension\Exception\HttpException;
use izi\prestashop\Extension\Exception\NetworkException;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Client\NetworkExceptionInterface;
use Psr\Http\Message\RequestFactoryInterface;
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

        try {
            $response = $this->client->sendRequest($request);
        } catch (NetworkExceptionInterface $e) {
            throw new NetworkException($e);
        }

        if (200 !== $response->getStatusCode()) {
            throw new HttpException($request, $response);
        }

        return $this->serializer->deserialize((string) $response->getBody(), Extension::class . '[]', 'json');
    }
}
