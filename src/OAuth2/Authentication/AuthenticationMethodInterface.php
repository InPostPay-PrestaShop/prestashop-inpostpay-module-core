<?php

declare(strict_types=1);

namespace izi\prestashop\OAuth2\Authentication;

use Psr\Http\Message\RequestInterface;

interface AuthenticationMethodInterface
{
    public function getIdentifier(): string;

    public function authenticate(RequestInterface $request, array &$payload, ClientCredentialsInterface $credentials): RequestInterface;
}
