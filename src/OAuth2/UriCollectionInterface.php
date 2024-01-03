<?php

declare(strict_types=1);

namespace izi\prestashop\OAuth2;

interface UriCollectionInterface
{
    public function getAuthorizationEndpointUri(): string;

    public function getTokenEndpointUri(): string;
}
