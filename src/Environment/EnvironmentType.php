<?php

declare(strict_types=1);

namespace izi\prestashop\Environment;

use izi\prestashop\Enum\IntEnum;
use izi\prestashop\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @method static self Uat()
 * @method static self Production()
 * @method static self Sandbox()
 */
final class EnvironmentType extends IntEnum implements TranslatableInterface
{
    private const UAT = 1;
    private const PRODUCTION = 2;
    private const SANDBOX = 3;

    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        switch ($this) {
            case self::Uat():
                return $translator->trans('UAT', [], 'Modules.Inpostizi.Environment', $locale);
            case self::Production():
                return $translator->trans('Production', [], 'Modules.Inpostizi.Environment', $locale);
            case self::Sandbox():
                return $translator->trans('Sandbox', [], 'Modules.Inpostizi.Environment', $locale);
            default:
                throw new \LogicException('Unreachable statement.');
        }
    }
}
