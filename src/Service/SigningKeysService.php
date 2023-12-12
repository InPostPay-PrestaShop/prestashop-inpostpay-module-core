<?php

namespace izi\prestashop\Service;

use izi\Controller;
use izi\prestashop\DTO\SigningKeyData;
use izi\prestashop\InpostIziPayPrestashop;
use izi\prestashop\SystemClock;
use Psr\Clock\ClockInterface;

class SigningKeysService
{
    private const CACHE_CONFIG_KEY = 'izi_signing_keys_json';

    private $client;
    private $clock;

    private $keys;

    public function __construct(Controller $client = null, ClockInterface $clock = null)
    {
        $this->client = $client ?? InpostIziPayPrestashop::getInstance()->getController();
        $this->clock = $clock ?? SystemClock::fromSystemTimezone();
    }

    public function getKeyData(string $version): ?SigningKeyData
    {
        $cachedKeys = $this->getCachedKeys();

        if (null !== $cachedKeys && $key = $this->findKeyByVersion($cachedKeys, $version)) {
            return new SigningKeyData($key, $cachedKeys->merchant_external_id);
        }

        $newKeys = $this->fetchSigningKeys();
        $cachedKeys = $this->refreshKeysCache($newKeys);

        if (null === $key = $this->findKeyByVersion($cachedKeys, $version)) {
            return null;
        }

        return new SigningKeyData($key, $cachedKeys->merchant_external_id);
    }

    private function getCachedKeys()
    {
        if (isset($this->keys)) {
            return $this->keys;
        }

        if (!$keys = \Configuration::get(self::CACHE_CONFIG_KEY)) {
            return null;
        }

        if (null === $keys = json_decode($keys, false)) {
            return null;
        }

        if (!isset($keys->expire_time) || $this->clock->now()->getTimestamp() >= $keys->expire_time) {
            return null;
        }

        return $this->keys = $keys;
    }

    private function findKeyByVersion($keys, string $version)
    {
        if (empty($keys->public_keys)) {
            return null;
        }

        foreach ($keys->public_keys as $key) {
            if ($version === $key->version) {
                return $key;
            }
        }

        return null;
    }

    private function fetchSigningKeys()
    {
        $response = $this->client->getSignatureKeys();

        if (empty($response) || !isset($response->public_keys)) {
            throw new \RuntimeException('Could not get signing keys from the API.');
        }

        return $response;
    }

    private function refreshKeysCache($keys)
    {
        $keys->expire_time = $this->clock->now()->modify('+1 day')->getTimestamp();

        foreach ($keys->public_keys as $key) {
            $key->hash = hash('sha256', $key->public_key_base64);
        }

        \Configuration::updateValue(self::CACHE_CONFIG_KEY, json_encode($keys));

        return $this->keys = $keys;
    }
}
