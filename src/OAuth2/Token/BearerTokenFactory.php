<?php

declare(strict_types=1);

namespace izi\prestashop\OAuth2\Token;

use izi\prestashop\OAuth2\Exception\UnexpectedValueException;

final class BearerTokenFactory implements AccessTokenFactoryInterface
{
    use AccessTokenFactoryTrait;

    public function createToken(array $data): AccessTokenInterface
    {
        if (0 !== strcasecmp(BearerToken::TYPE, $data['token_type'])) {
            throw new UnexpectedValueException(\sprintf('Expected token type to be "%s", "%s" given.', BearerToken::TYPE, $data['token_type']));
        }

        return new BearerToken(
            $data['access_token'],
            $this->getExpiresAt($data),
            $data['refresh_token'] ?? null,
            $this->getScopes($data)
        );
    }
}
