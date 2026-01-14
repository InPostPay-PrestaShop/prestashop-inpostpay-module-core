<?php

declare(strict_types=1);

namespace izi\prestashop\View\Widget;

use izi\prestashop\Enum\StringEnum;
use izi\prestashop\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @method static self ExtraSmall()
 * @method static self Small()
 * @method static self Medium()
 * @method static self Large()
 * @method static self ExtraLarge()
 */
final class Size extends StringEnum implements TranslatableInterface
{
    private const EXTRA_SMALL = 'size-xs';
    private const SMALL = 'size-sm';
    private const MEDIUM = 'size-md';
    private const LARGE = 'size-lg';
    private const EXTRA_LARGE = 'size-xl';

    /**
     * @return self size corresponding to the default widget behavior if no size is set in the "variation" attribute
     */
    public static function getDefault(): self
    {
        return self::Large();
    }

    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        switch ($this) {
            case self::ExtraSmall():
                return $translator->trans('Extra small', [], 'Modules.Inpostizi.Gui', $locale);
            case self::Small():
                return $translator->trans('Small', [], 'Modules.Inpostizi.Gui', $locale);
            case self::Medium():
                return $translator->trans('Medium', [], 'Modules.Inpostizi.Gui', $locale);
            case self::Large():
                return $translator->trans('Large', [], 'Modules.Inpostizi.Gui', $locale);
            case self::ExtraLarge():
                return $translator->trans('Extra large', [], 'Modules.Inpostizi.Gui', $locale);
            default:
                throw new \LogicException('Unreachable statement.');
        }
    }
}
