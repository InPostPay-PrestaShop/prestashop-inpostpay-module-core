<?php

declare(strict_types=1);

namespace izi\prestashop\Product\Description;

use izi\prestashop\Enum\IntEnum;
use izi\prestashop\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @method static self LongWithShortFallback()
 * @method static self LongOnly()
 * @method static self ShortOnly()
 */
final class DescriptionSource extends IntEnum implements TranslatableInterface
{
    private const LONG_WITH_SHORT_FALLBACK = 0;
    private const LONG_ONLY = 1;
    private const SHORT_ONLY = 2;

    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        switch ($this) {
            case self::LongWithShortFallback():
                return $translator->trans('Description (summary if the description is empty)', [], 'Modules.Inpostizi.Product', $locale);
            case self::LongOnly():
                return $translator->trans('Description only', [], 'Modules.Inpostizi.Product', $locale);
            case self::ShortOnly():
                return $translator->trans('Summary only', [], 'Modules.Inpostizi.Product', $locale);
            default:
                throw new \LogicException('Not implemented.');
        }
    }
}
