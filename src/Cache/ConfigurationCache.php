<?php

declare(strict_types=1);

namespace izi\prestashop\Cache;

use izi\prestashop\Cache\Exception\InvalidArgumentException;
use izi\prestashop\Cache\Exception\RuntimeException;
use izi\prestashop\Configuration\ConfigurationInterface;
use Psr\Clock\ClockInterface;
use Psr\SimpleCache\CacheInterface;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class ConfigurationCache implements CacheInterface
{
    private const CONFIG_KEY_PATTERN = 'INPOST_PAY_CACHE_%s';

    /**
     * @var ConfigurationInterface
     */
    private $configuration;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var ClockInterface
     */
    private $clock;

    public function __construct(ConfigurationInterface $configuration, SerializerInterface $serializer, ClockInterface $clock)
    {
        $this->configuration = $configuration;
        $this->serializer = $serializer;
        $this->clock = $clock;
    }

    public static function getConfigKeyPrefix(): string
    {
        return \sprintf(self::CONFIG_KEY_PATTERN, '');
    }

    /**
     * @return mixed
     */
    public function get($key, $default = null)
    {
        $configKey = $this->validateKey($key);

        try {
            $configValue = $this->configuration->get($configKey);
        } catch (\Exception $e) {
            throw new RuntimeException($e->getMessage(), $e->getCode(), $e);
        }

        try {
            $item = $this->deserializeCacheItem($configValue);
        } catch (ExceptionInterface $e) {
            return $default;
        }

        if (null === $item) {
            return $default;
        }

        if (null !== $item['expiry'] && $item['expiry'] < $this->clock->now()) {
            return $default;
        }

        return $item['value'];
    }

    public function set($key, $value, $ttl = null): bool
    {
        $configKey = $this->validateKey($key);

        try {
            $item = $this->createCacheItem($value, $ttl);
        } catch (ExceptionInterface $e) {
            throw new RuntimeException($e->getMessage(), $e->getCode(), $e);
        }

        try {
            $this->configuration->set($configKey, json_encode($item));

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function delete($key): bool
    {
        $configKey = $this->validateKey($key);

        try {
            $this->configuration->remove($configKey);

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function clear(): bool
    {
        try {
            $this->configuration->removeMatching(\sprintf(self::CONFIG_KEY_PATTERN, '*'));

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function getMultiple($keys, $default = null): iterable
    {
        $this->validateKeys($keys);

        $result = [];

        foreach ($keys as $key) {
            $value = $this->get($key, $default);
            $result[$key] = $value;
        }

        return $result;
    }

    public function setMultiple($values, $ttl = null): bool
    {
        if (!is_iterable($values)) {
            throw new InvalidArgumentException(\sprintf('Cache values must be an array or a Traversable, "%s" given.', get_debug_type($values)));
        }

        $success = true;
        foreach ($values as $key => $value) {
            $success = $this->set($key, $value, $ttl) && $success;
        }

        return $success;
    }

    public function deleteMultiple($keys): bool
    {
        $this->validateKeys($keys);

        $success = true;
        foreach ($keys as $key) {
            $success = $this->delete($key) && $success;
        }

        return $success;
    }

    public function has($key): bool
    {
        $configKey = $this->validateKey($key);

        return $this->configuration->has($configKey);
    }

    private function validateKey($key): string
    {
        if (!\is_string($key)) {
            throw new InvalidArgumentException(\sprintf('Cache key must be a string, "%s" given.', get_debug_type($key)));
        }

        if ('' === $key) {
            throw new InvalidArgumentException('Cache key length must be greater than zero.');
        }

        $configKey = \sprintf(self::CONFIG_KEY_PATTERN, $key);

        if (!$this->configuration->isValidKey($configKey)) {
            throw new InvalidArgumentException(\sprintf('Cache key "%s" is invalid.', $key));
        }

        return $configKey;
    }

    private function validateKeys($keys): void
    {
        if (is_iterable($keys)) {
            return;
        }

        throw new InvalidArgumentException(\sprintf('Cache keys must be an array or a Traversable, "%s" given.', get_debug_type($keys)));
    }

    private function createCacheItem($value, $ttl): array
    {
        $expiry = $this->getExpiry($ttl);

        if (\is_object($value)) {
            $class = \get_class($value);
            $value = $this->serializeValue($value);
        } else {
            $class = null;
        }

        return [
            'value' => $value,
            'class' => $class,
            'expiry' => null !== $expiry ? $expiry->format('U.u') : null,
        ];
    }

    private function serializeValue($value): string
    {
        try {
            return $this->serializer->serialize($value, 'json');
        } catch (ExceptionInterface $e) {
            throw new InvalidArgumentException('Unable to serialize cache value.', $e->getCode(), $e);
        }
    }

    private function getExpiry($ttl): ?\DateTimeImmutable
    {
        if (null === $ttl) {
            return null;
        }

        if (\is_int($ttl)) {
            $ttl = new \DateInterval(\sprintf('PT%dS', $ttl));
        } elseif (!$ttl instanceof \DateInterval) {
            throw new InvalidArgumentException(\sprintf('TTL must be an integer, a DateInterval or null, "%s" given.', get_debug_type($ttl)));
        }

        return $this->clock->now()->add($ttl);
    }

    private function deserializeCacheItem($value): ?array
    {
        if (null === $value) {
            return null;
        }

        $item = json_decode((string) $value, true);

        if (!\is_array($item)) {
            return null;
        }

        if (isset($item['class'])) {
            $item['value'] = $this->serializer->deserialize($item['value'], $item['class'], 'json');
        }

        if (isset($item['expiry'])) {
            $item['expiry'] = \DateTimeImmutable::createFromFormat('U.u', $item['expiry']);
        }

        return $item;
    }
}
