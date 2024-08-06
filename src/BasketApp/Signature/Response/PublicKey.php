<?php

declare(strict_types=1);

namespace izi\prestashop\BasketApp\Signature\Response;

use izi\prestashop\Serializer\Normalizer\DenormalizableInterface;
use Symfony\Component\Serializer\Exception\UnexpectedValueException;

final class PublicKey implements \JsonSerializable, DenormalizableInterface
{
    /**
     * @var string
     */
    private $public_key_base64;

    /**
     * @var string
     */
    private $version;

    /**
     * @var string|null
     */
    private $hash;

    public function __construct(string $public_key_base64, string $version)
    {
        $this->public_key_base64 = $public_key_base64;
        $this->version = $version;
    }

    public static function denormalize(array $data): self
    {
        if (!isset($data['public_key_base64'], $data['version'])) {
            throw new UnexpectedValueException('Array data does not contain all of the required parameters.');
        }

        $key = new self($data['public_key_base64'], $data['version']);
        if (isset($data['hash'])) {
            $key->hash = $data['hash'];
        }

        return $key;
    }

    public function getBase64Encoded(): string
    {
        return $this->public_key_base64;
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }

    public function getHash(): string
    {
        return $this->hash ?? ($this->hash = hash('sha256', $this->public_key_base64));
    }

    public function getPemFormatted(): string
    {
        return "-----BEGIN PUBLIC KEY-----\n" . $this->public_key_base64 . "\n-----END PUBLIC KEY-----";
    }
}
