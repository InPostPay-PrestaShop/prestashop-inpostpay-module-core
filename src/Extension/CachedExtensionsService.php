<?php

declare(strict_types=1);

namespace izi\prestashop\Extension;

use Psr\Cache\CacheItemPoolInterface;

final class CachedExtensionsService implements ExtensionsServiceInterface
{
    public const DEFAULT_TTL = 600;
    private const CACHE_KEY = 'inpostizi_extensions';

    /**
     * @var ExtensionsServiceInterface
     */
    private $service;

    /**
     * @var CacheItemPoolInterface
     */
    private $cache;

    /**
     * @var int
     */
    private $ttl;

    public function __construct(ExtensionsServiceInterface $service, CacheItemPoolInterface $cache, int $ttl = self::DEFAULT_TTL)
    {
        $this->service = $service;
        $this->cache = $cache;
        $this->ttl = $ttl;
    }

    public function getExtensions(): array
    {
        $item = $this->cache->getItem(self::CACHE_KEY);

        if ($item->isHit()) {
            return $item->get();
        }

        $extensions = $this->service->getExtensions();

        $item->set($extensions);
        $item->expiresAfter($this->ttl);
        $this->cache->save($item);

        return $extensions;
    }
}
