<?php

declare(strict_types=1);

namespace izi\prestashop\OAuth2\Token;

use Psr\Http\Message\RequestInterface;

interface AccessTokenInterface
{
    public function getAccessToken(): string;

    public function getType(): string;

    public function getExpiresAt(): ?\DateTimeImmutable;

    public function getRefreshToken(): ?string;

    /**
     * @return string[]|null
     */
    public function getScopes(): ?array;

    public function authorize(RequestInterface $request): RequestInterface;
}
