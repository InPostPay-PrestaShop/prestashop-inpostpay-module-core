<?php

declare(strict_types=1);

namespace izi\prestashop\Http\Client\Factory;

use GuzzleHttp\Client;
use izi\prestashop\Http\Client\Adapter\Guzzle5Adapter;
use Psr\Http\Client\ClientInterface;

class GuzzleClientFactory implements ClientFactoryInterface
{
    /**
     * @var int
     */
    private $timeout;

    public function __construct(int $timeout = 10)
    {
        $this->timeout = $timeout;
    }

    public function create(): ClientInterface
    {
        if (!class_exists(Client::class)) {
            throw new \RuntimeException(sprintf('Class %s does not exist', Client::class));
        }

        if (is_subclass_of(Client::class, ClientInterface::class)) {
            return new Client([
                'timeout' => $this->timeout,
            ]);
        }

        $client = new Client([
            'defaults' => [
                'timeout' => $this->timeout,
            ],
        ]);

        return new Guzzle5Adapter($client);
    }
}
