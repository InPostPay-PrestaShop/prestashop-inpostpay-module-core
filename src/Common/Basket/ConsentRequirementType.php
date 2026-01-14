<?php

declare(strict_types=1);

namespace izi\prestashop\Common\Basket;

use izi\prestashop\Enum\StringEnum;
use izi\prestashop\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @method static self Optional()
 * @method static self RequiredOnce()
 * @method static self RequiredAlways()
 */
final class ConsentRequirementType extends StringEnum implements TranslatableInterface
{
    private const OPTIONAL = 'OPTIONAL';
    private const REQUIRED_ONCE = 'REQUIRED_ONCE';
    private const REQUIRED_ALWAYS = 'REQUIRED_ALWAYS';

    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        switch ($this) {
            case self::Optional():
                return $translator->trans('Optional', [], 'Modules.Inpostizi.Consent', $locale);
            case self::RequiredOnce():
                return $translator->trans('Required once', [], 'Modules.Inpostizi.Consent', $locale);
            case self::RequiredAlways():
                return $translator->trans('Always required', [], 'Modules.Inpostizi.Consent', $locale);
            default:
                throw new \LogicException('Unreachable statement.');
        }
    }
}
