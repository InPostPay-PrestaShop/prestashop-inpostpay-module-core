<?php

declare(strict_types=1);

namespace izi\prestashop\BasketApp\Signature\Response;

final class SigningKey implements \JsonSerializable
{
    /**
     * @var string
     */
    private $merchant_external_id;

    /**
     * @var PublicKey
     */
    private $public_key;

    public function __construct(string $merchant_external_id, PublicKey $public_key)
    {
        $this->merchant_external_id = $merchant_external_id;
        $this->public_key = $public_key;
    }

    public function getMerchantId(): string
    {
        return $this->merchant_external_id;
    }

    public function getPublicKey(): PublicKey
    {
        return $this->public_key;
    }

    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
