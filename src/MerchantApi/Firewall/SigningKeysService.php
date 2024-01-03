<?php

declare(strict_types=1);

namespace izi\prestashop\MerchantApi\Firewall;

use izi\prestashop\BasketApp\Signature\Response\SigningKey;
use izi\prestashop\BasketApp\Signature\Response\SigningKeys;
use izi\prestashop\BasketApp\Signature\SigningKeysApiClientInterface;
use Psr\SimpleCache\CacheInterface;

final class SigningKeysService implements SigningKeysServiceInterface
{
    private const CACHE_KEY = 'SIGNING_KEYS';

    /**
     * @var SigningKeysApiClientInterface
     */
    private $client;

    /**
     * @var CacheInterface
     */
    private $cache;

    /**
     * @var SigningKeys|null
     */
    private $keys;

    public function __construct(SigningKeysApiClientInterface $client, CacheInterface $cache)
    {
        $this->client = $client;
        $this->cache = $cache;
    }

    public function getSigningKey(string $version): ?SigningKey
    {
        $cachedKeys = $this->getCachedKeys();

        if (null !== $cachedKeys && $publicKey = $cachedKeys->getPublicKey($version)) {
            return new SigningKey($cachedKeys->getMerchantId(), $publicKey);
        }

        $newKeys = $this->refreshKeysCache();

        if (null === $publicKey = $newKeys->getPublicKey($version)) {
            return null;
        }

        return new SigningKey($newKeys->getMerchantId(), $publicKey);
    }

    private function getCachedKeys(): ?SigningKeys
    {
        if (isset($this->keys)) {
            return $this->keys;
        }

        $keys = $this->cache->get(self::CACHE_KEY);

        if (!$keys instanceof SigningKeys) {
            return null;
        }

        return $this->keys = $keys;
    }

    private function refreshKeysCache(): SigningKeys
    {
        $keys = $this->client->getSigningKeys();

        foreach ($keys as $key) {
            $key->getHash();
        }

        $this->cache->set(self::CACHE_KEY, $keys, new \DateInterval('P1D'));

        return $this->keys = $keys;
    }
}
