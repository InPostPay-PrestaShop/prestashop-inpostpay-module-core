<?php

declare(strict_types=1);

namespace izi\prestashop\Extension\EventListener;

use izi\prestashop\CacheClearer\CacheClearerInterface;
use izi\prestashop\Extension\Event\ExtensionEvent;
use izi\prestashop\Hook\VersionRange;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class ClearCacheListener implements EventSubscriberInterface
{
    /**
     * @var CacheClearerInterface
     */
    private $clearer;

    /**
     * @var string
     */
    private $psVersion;

    public function __construct(CacheClearerInterface $clearer, string $psVersion = _PS_VERSION_)
    {
        $this->clearer = $clearer;
        $this->psVersion = $psVersion;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ExtensionEvent::INSTALLED => 'clearCache',
            ExtensionEvent::UNINSTALLED => 'clearCache',
            ExtensionEvent::UPGRADED => 'clearCache',
            ExtensionEvent::ENABLED => 'onStatusUpdate',
            ExtensionEvent::DISABLED => 'onStatusUpdate',
        ];
    }

    public function clearCache(): void
    {
        $this->clearer->clear();
    }

    public function onStatusUpdate(): void
    {
        static $versionRange;
        $versionRange = $versionRange ?? new VersionRange(null, '8.0');

        if ($versionRange->contains($this->psVersion)) {
            return;
        }

        $this->clearer->clear();
    }
}
