<?php

declare(strict_types=1);

namespace izi\prestashop\OAuth2\Authentication;

use Psr\Http\Message\RequestInterface;

final class ClientSecretPost implements AuthenticationMethodInterface
{
    public const IDENTIFIER = 'client_secret_post';

    public function getIdentifier(): string
    {
        return self::IDENTIFIER;
    }

    public function authenticate(RequestInterface $request, array &$payload, ClientCredentialsInterface $credentials): RequestInterface
    {
        if (null === $clientSecret = $credentials->getClientSecret()) {
            throw new \LogicException(\sprintf('"%s" authentication method cannot be used if the client was not issued client credentials.', self::IDENTIFIER));
        }

        $payload['client_id'] = $credentials->getClientId();
        $payload['client_secret'] = $clientSecret;

        return $request;
    }
}
