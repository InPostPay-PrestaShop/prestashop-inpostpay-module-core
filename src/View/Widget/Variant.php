<?php

declare(strict_types=1);

namespace izi\prestashop\View\Widget;

use izi\prestashop\Enum\StringEnum;
use izi\prestashop\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @method static self Primary()
 * @method static self Secondary()
 */
final class Variant extends StringEnum implements TranslatableInterface
{
    private const PRIMARY = 'primary';
    private const SECONDARY = 'secondary';

    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        switch ($this) {
            case self::Primary():
                return $translator->trans('Yellow', [], 'Modules.Inpostizi.Gui', $locale);
            case self::Secondary():
                return $translator->trans('Black', [], 'Modules.Inpostizi.Gui', $locale);
            default:
                throw new \LogicException('Unreachable statement.');
        }
    }
}
