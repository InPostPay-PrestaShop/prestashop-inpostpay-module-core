<?php

declare(strict_types=1);

namespace izi\prestashop\BasketApp\Signature;

use izi\prestashop\BasketApp\Exception\PublicKeyNotFoundException;
use izi\prestashop\BasketApp\Signature\Response\SigningKey;
use izi\prestashop\BasketApp\Signature\Response\SigningKeys;

interface SigningKeysApiClientInterface
{
    /**
     * @throws PublicKeyNotFoundException
     */
    public function getSigningKey(string $version): SigningKey;

    public function getSigningKeys(): SigningKeys;
}
