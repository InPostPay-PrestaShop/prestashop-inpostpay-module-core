<?php

declare(strict_types=1);

namespace izi\prestashop\OAuth2\Grant;

final class ClientCredentialsGrant extends AbstractGrant
{
    public const IDENTIFIER = 'client_credentials';

    public function getIdentifier(): string
    {
        return self::IDENTIFIER;
    }
}
