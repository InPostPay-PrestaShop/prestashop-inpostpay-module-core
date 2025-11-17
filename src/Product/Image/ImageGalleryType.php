<?php

declare(strict_types=1);

namespace izi\prestashop\Product\Image;

use izi\prestashop\Enum\IntEnum;
use izi\prestashop\Translation\LegacyTranslator;
use izi\prestashop\Translation\TranslatableInterface;

/**
 * @method static self AllImages()
 * @method static self OnlyCoverImage()
 */
final class ImageGalleryType extends IntEnum implements TranslatableInterface
{
    private const ALL_IMAGES = 0;
    private const ONLY_COVER_IMAGE = 1;

    public function trans(LegacyTranslator $translator): string
    {
        switch ($this) {
            case self::AllImages():
                return $translator->l('All images', 'imagegallerytype');
            case self::OnlyCoverImage():
                return $translator->l('Only cover image', 'imagegallerytype');
            default:
                throw new \LogicException('Not implemented.');
        }
    }
}
