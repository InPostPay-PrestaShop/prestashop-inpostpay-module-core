<?php

namespace izi\prestashop\View\Widget;

use izi\prestashop\Enum\StringEnum;
use izi\prestashop\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @method static self Rounded()
 * @method static self Round()
 */
final class FrameStyle extends StringEnum implements TranslatableInterface
{
    private const ROUNDED = 'rounded';
    private const ROUND = 'round';

    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        switch ($this) {
            case self::Rounded():
                return $translator->trans('Rounded', [], 'Modules.Inpostizi.Gui', $locale);
            case self::Round():
                return $translator->trans('Round', [], 'Modules.Inpostizi.Gui', $locale);
            default:
                throw new \LogicException('Unreachable statement.');
        }
    }
}
