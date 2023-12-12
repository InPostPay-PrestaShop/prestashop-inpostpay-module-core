<?php

namespace izi\prestashop\DTO;

final class SigningKeyData
{
    private $key;
    private $merchantId;

    /**
     * @param object{public_key_base64: string, hash: string} $key
     */
    public function __construct($key, string $merchantId)
    {
        $this->key = $key;
        $this->merchantId = $merchantId;
    }

    /**
     * @return object{public_key_base64: string, hash: string}
     */
    public function getKey()
    {
        return $this->key;
    }

    public function getMerchantId(): string
    {
        return $this->merchantId;
    }
}
