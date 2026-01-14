<?php

declare(strict_types=1);

namespace izi\prestashop\Http\Client\Factory;

use GuzzleHttp\Client;
use izi\prestashop\Http\Client\Adapter\Guzzle5Adapter;
use Psr\Http\Client\ClientInterface;

final class GuzzleClientFactory implements ClientFactoryInterface
{
    public function create(array $options = []): ClientInterface
    {
        if (!class_exists(Client::class)) {
            throw new \RuntimeException(\sprintf('Class "%s" does not exist.', Client::class));
        }

        $options = $this->convertOptions($options);

        if (is_subclass_of(Client::class, ClientInterface::class)) {
            return new Client($options);
        }

        $client = new Client([
            'defaults' => $options,
        ]);

        return new Guzzle5Adapter($client);
    }

    private function convertOptions(array $options): array
    {
        $guzzleOptions = [];

        if (isset($options['timeout'])) {
            $guzzleOptions['connect_timeout'] = $options['timeout'];
        }

        if (isset($options['max_duration'])) {
            $guzzleOptions['timeout'] = $options['max_duration'];
        }

        return $guzzleOptions;
    }
}
