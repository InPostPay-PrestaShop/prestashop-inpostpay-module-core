<?php

declare(strict_types=1);

namespace izi\prestashop\BasketApp\Signature\Response;

/**
 * @implements \IteratorAggregate<PublicKey>
 */
final class SigningKeys implements \IteratorAggregate, \JsonSerializable
{
    /**
     * @var string
     */
    private $merchant_external_id;

    /**
     * @var PublicKey[]
     */
    private $public_keys;

    /**
     * @param PublicKey[] $public_keys
     */
    public function __construct(string $merchant_external_id, array $public_keys)
    {
        $this->merchant_external_id = $merchant_external_id;
        $this->public_keys = $public_keys;
    }

    public function getMerchantId(): string
    {
        return $this->merchant_external_id;
    }

    /**
     * @return PublicKey[]
     */
    public function getPublicKeys(): array
    {
        return $this->public_keys;
    }

    public function getIterator(): \Iterator
    {
        return new \ArrayIterator($this->public_keys);
    }

    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }

    public function getPublicKey(string $version): ?PublicKey
    {
        foreach ($this->public_keys as $publicKey) {
            if ($version === $publicKey->getVersion()) {
                return $publicKey;
            }
        }

        return null;
    }
}
