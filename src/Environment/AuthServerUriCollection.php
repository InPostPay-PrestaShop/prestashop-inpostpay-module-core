<?php

declare(strict_types=1);

namespace izi\prestashop\Environment;

use izi\prestashop\OAuth2\UriCollectionInterface;

final class AuthServerUriCollection implements UriCollectionInterface
{
    /**
     * @var EnvironmentInterface
     */
    private $environment;

    public function __construct(EnvironmentInterface $environment)
    {
        $this->environment = $environment;
    }

    public function getAuthorizationEndpointUri(): string
    {
        throw new \LogicException('Not implemented.');
    }

    public function getTokenEndpointUri(): string
    {
        return $this->environment->getAuthServerTokenEndpointUri();
    }
}
