<?php

declare(strict_types=1);

namespace izi\prestashop\OAuth2\Token;

final class InMemoryTokenRepository implements AccessTokenRepositoryInterface
{
    private $accessToken;

    public function getToken(): ?AccessTokenInterface
    {
        return $this->accessToken;
    }

    public function saveToken(AccessTokenInterface $accessToken): void
    {
        $this->accessToken = $accessToken;
    }

    public function deleteToken(): void
    {
        $this->accessToken = null;
    }
}
