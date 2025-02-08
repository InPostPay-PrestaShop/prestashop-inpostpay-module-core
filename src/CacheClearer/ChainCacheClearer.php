<?php

declare(strict_types=1);

namespace izi\prestashop\CacheClearer;

final class ChainCacheClearer implements CacheClearerInterface
{
    /**
     * @var iterable|CacheClearerInterface[]
     */
    private $clearers;

    /**
     * @param iterable<CacheClearerInterface> $clearers
     */
    public function __construct(iterable $clearers)
    {
        $this->clearers = $clearers;
    }

    public function clear(): void
    {
        foreach ($this->clearers as $clearer) {
            $clearer->clear();
        }
    }
}
