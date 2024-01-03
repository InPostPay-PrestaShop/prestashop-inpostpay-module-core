<?php

declare(strict_types=1);

namespace izi\prestashop\OAuth2\Authentication;

interface ClientCredentialsInterface
{
    public function getClientId(): string;

    public function getClientSecret(): ?string;
}
