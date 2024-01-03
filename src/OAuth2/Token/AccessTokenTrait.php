<?php

declare(strict_types=1);

namespace izi\prestashop\OAuth2\Token;

/**
 * @mixin AccessTokenInterface
 */
trait AccessTokenTrait
{
    /**
     * @var string
     */
    private $accessToken;

    /**
     * @var \DateTimeImmutable|null
     */
    private $expiresAt;

    /**
     * @var string|null
     */
    private $refreshToken;

    /**
     * @var string[]|null
     */
    private $scopes;

    public function getAccessToken(): string
    {
        return $this->accessToken;
    }

    public function getExpiresAt(): ?\DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function getRefreshToken(): ?string
    {
        return $this->refreshToken;
    }

    public function getScopes(): ?array
    {
        return $this->scopes;
    }
}
