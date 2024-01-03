<?php

declare(strict_types=1);

namespace izi\prestashop\OAuth2\Authentication;

use Psr\Http\Message\RequestInterface;

final class None implements AuthenticationMethodInterface
{
    public const IDENTIFIER = 'none';

    public function getIdentifier(): string
    {
        return self::IDENTIFIER;
    }

    public function authenticate(RequestInterface $request, array &$payload, ClientCredentialsInterface $credentials): RequestInterface
    {
        $payload['client_id'] = $credentials->getClientId();

        return $request;
    }
}
