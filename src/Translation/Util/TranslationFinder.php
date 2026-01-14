<?php

declare(strict_types=1);

namespace izi\prestashop\Translation\Util;

use PrestaShop\PrestaShop\Core\Exception\FileNotFoundException;
use PrestaShop\PrestaShop\Core\Translation\Exception\TranslationFilesNotFoundException;
use PrestaShop\PrestaShop\Core\Translation\Storage\Finder\TranslationFinder as CoreTranslationFinder;
use PrestaShopBundle\Translation\Provider\TranslationFinder as LegacyTranslationFinder;
use Symfony\Component\Translation\MessageCatalogue;

class TranslationFinder
{
    /**
     * @var CoreTranslationFinder|LegacyTranslationFinder
     */
    private $finder;

    public function __construct()
    {
        $this->finder = self::createNativeFinder();
    }

    public function getCatalogue(string $directory, string $locale): ?MessageCatalogue
    {
        $directory = \sprintf('%s/%s', rtrim($directory), $locale);

        if (!is_dir($directory)) {
            return null;
        }

        try {
            return $this->finder->getCatalogueFromPaths([$directory], $locale);
        } catch (TranslationFilesNotFoundException|FileNotFoundException $e) {
            return null;
        }
    }

    /**
     * @return CoreTranslationFinder|LegacyTranslationFinder
     */
    private static function createNativeFinder(): object
    {
        if (class_exists(CoreTranslationFinder::class)) {
            return new CoreTranslationFinder();
        }

        if (class_exists(LegacyTranslationFinder::class)) {
            return new LegacyTranslationFinder();
        }

        throw new \RuntimeException('Unable to create translation finder: base class not found.');
    }
}
