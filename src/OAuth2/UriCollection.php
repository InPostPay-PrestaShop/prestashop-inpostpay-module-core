<?php

declare(strict_types=1);

namespace izi\prestashop\OAuth2;

final class UriCollection implements UriCollectionInterface
{
    /**
     * @var string
     */
    private $authorizationEndpointUri;

    /**
     * @var string
     */
    private $tokenEndpointUri;

    public function __construct(string $authorizationEndpointUri, string $tokenEndpointUri)
    {
        $this->authorizationEndpointUri = $authorizationEndpointUri;
        $this->tokenEndpointUri = $tokenEndpointUri;
    }

    public function getAuthorizationEndpointUri(): string
    {
        return $this->authorizationEndpointUri;
    }

    public function getTokenEndpointUri(): string
    {
        return $this->tokenEndpointUri;
    }
}
