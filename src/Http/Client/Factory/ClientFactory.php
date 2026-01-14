<?php

declare(strict_types=1);

namespace izi\prestashop\Http\Client\Factory;

use GuzzleHttp\Client;
use Psr\Container\ContainerInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpClient\Psr18Client;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

final class ClientFactory implements ClientFactoryInterface, ServiceSubscriberInterface
{
    private const DEFAULT_OPTIONS = [
        'timeout' => 3.,
        'max_duration' => 10.,
        'max_redirects' => 0,
    ];

    /**
     * @var ContainerInterface
     */
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public static function getSubscribedServices(): array
    {
        return [
            '?' . ClientInterface::class,
            GuzzleClientFactory::class,
            ResponseFactoryInterface::class,
            StreamFactoryInterface::class,
        ];
    }

    public function create(array $options = []): ClientInterface
    {
        $options = array_merge(self::DEFAULT_OPTIONS, $options);

        if (class_exists(Client::class)) {
            return $this->container->get(GuzzleClientFactory::class)->create($options);
        }

        if (null !== $client = $this->getDefaultClient($options)) {
            return $client;
        }

        return $this->createSymfonyClient($options);
    }

    private function getDefaultClient(array $options): ?ClientInterface
    {
        if (!$this->container->has(ClientInterface::class)) {
            return null;
        }

        $client = $this->container->get(ClientInterface::class);

        if (!\is_callable([$client, 'withOptions'])) {
            return null;
        }

        return $client->withOptions($options);
    }

    private function createSymfonyClient(array $options): ClientInterface
    {
        $client = HttpClient::create($options);

        return new Psr18Client(
            $client,
            $this->container->get(ResponseFactoryInterface::class),
            $this->container->get(StreamFactoryInterface::class)
        );
    }
}
