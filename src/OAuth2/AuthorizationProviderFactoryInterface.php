<?php

declare(strict_types=1);

namespace izi\prestashop\OAuth2;

use izi\prestashop\OAuth2\Authentication\ClientCredentialsInterface;
use izi\prestashop\OAuth2\Token\AccessTokenRepositoryInterface;

interface AuthorizationProviderFactoryInterface
{
    public function create(UriCollectionInterface $uriCollection, ClientCredentialsInterface $credentials, ?AccessTokenRepositoryInterface $tokenRepository = null): AuthorizationProviderInterface;
}
