<?php

declare(strict_types=1);

namespace InPost\Izi\Upgrade;

use izi\prestashop\CacheClearer\CacheClearerInterface;
use izi\prestashop\CacheClearer\SymfonyCacheClearer;

/**
 * @deprecated
 */
final class CacheClearer
{
    /**
     * @var self
     */
    private static $instance;

    /**
     * @var CacheClearerInterface
     */
    private $clearer;

    private function __construct(CacheClearerInterface $clearer)
    {
        $this->clearer = $clearer;
    }

    public static function getInstance(): self
    {
        @trigger_error(sprintf('Class "%s" is deprecated. Use "%s" instead.', self::class, SymfonyCacheClearer::class), E_USER_DEPRECATED);

        if (!isset(self::$instance)) {
            self::$instance = new self(SymfonyCacheClearer::getInstance());
        }

        return self::$instance;
    }

    public function clear(): void
    {
        $this->clearer->clear();
    }

    private function __clone()
    {
    }
}
