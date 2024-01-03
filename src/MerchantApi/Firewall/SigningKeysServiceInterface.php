<?php

declare(strict_types=1);

namespace izi\prestashop\MerchantApi\Firewall;

use izi\prestashop\BasketApp\Signature\Response\SigningKey;

interface SigningKeysServiceInterface
{
    public function getSigningKey(string $version): ?SigningKey;
}
