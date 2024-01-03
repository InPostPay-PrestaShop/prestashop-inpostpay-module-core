<?php

declare(strict_types=1);

namespace izi\prestashop\OAuth2\Token;

interface AccessTokenFactoryInterface
{
    /**
     * @param array $data decoded access token response data
     */
    public function createToken(array $data): AccessTokenInterface;
}
