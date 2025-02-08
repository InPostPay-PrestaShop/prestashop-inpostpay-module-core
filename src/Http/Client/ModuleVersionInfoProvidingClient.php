<?php

declare(strict_types=1);

namespace izi\prestashop\Http\Client;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

final class ModuleVersionInfoProvidingClient implements ClientInterface
{
    public const HEADER_NAME = 'inpay-plugin-version';

    /**
     * @var ClientInterface
     */
    private $client;

    /**
     * @var \Module
     */
    private $module;

    public function __construct(ClientInterface $client, \Module $module)
    {
        $this->client = $client;
        $this->module = $module;
    }

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $request = $request->withHeader(self::HEADER_NAME, $this->module->version);

        return $this->client->sendRequest($request);
    }
}
