<?php

declare(strict_types=1);

namespace izi\prestashop\OAuth2\Token;

use Psr\Http\Message\RequestInterface;

final class BearerToken implements AccessTokenInterface
{
    use AccessTokenTrait;

    public const TYPE = 'bearer';

    /**
     * @param string[]|null $scopes
     */
    public function __construct(string $accessToken, ?\DateTimeImmutable $expiresAt = null, ?string $refreshToken = null, ?array $scopes = null)
    {
        $this->accessToken = $accessToken;
        $this->expiresAt = $expiresAt;
        $this->refreshToken = $refreshToken;
        $this->scopes = $scopes;
    }

    public function getType(): string
    {
        return self::TYPE;
    }

    public function authorize(RequestInterface $request): RequestInterface
    {
        return $request->withHeader('Authorization', 'Bearer ' . $this->accessToken);
    }
}
