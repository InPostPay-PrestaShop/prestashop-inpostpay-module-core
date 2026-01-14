<?php

declare(strict_types=1);

namespace izi\prestashop\Product\Image;

use izi\prestashop\Enum\IntEnum;
use izi\prestashop\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @method static self AllImages()
 * @method static self OnlyCoverImage()
 */
final class ImageGalleryType extends IntEnum implements TranslatableInterface
{
    private const ALL_IMAGES = 0;
    private const ONLY_COVER_IMAGE = 1;

    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        switch ($this) {
            case self::AllImages():
                return $translator->trans('All images', [], 'Modules.Inpostizi.Product', $locale);
            case self::OnlyCoverImage():
                return $translator->trans('Only cover image', [], 'Modules.Inpostizi.Product', $locale);
            default:
                throw new \LogicException('Not implemented.');
        }
    }
}
