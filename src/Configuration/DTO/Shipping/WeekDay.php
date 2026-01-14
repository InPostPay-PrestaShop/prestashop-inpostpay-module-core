<?php

declare(strict_types=1);

namespace izi\prestashop\Configuration\DTO\Shipping;

use izi\prestashop\Enum\IntEnum;
use izi\prestashop\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @method static self Monday()
 * @method static self Tuesday()
 * @method static self Wednesday()
 * @method static self Thursday()
 * @method static self Friday()
 * @method static self Saturday()
 * @method static self Sunday()
 */
final class WeekDay extends IntEnum implements TranslatableInterface
{
    private const MONDAY = 1;
    private const TUESDAY = 2;
    private const WEDNESDAY = 3;
    private const THURSDAY = 4;
    private const FRIDAY = 5;
    private const SATURDAY = 6;
    private const SUNDAY = 7;

    public static function fromDateTime(\DateTimeInterface $dateTime): self
    {
        return self::from((int) $dateTime->format('N'));
    }

    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        switch ($this) {
            case self::Monday():
                return $translator->trans('Monday', [], 'Admin.Shopparameters.Feature', $locale);
            case self::Tuesday():
                return $translator->trans('Tuesday', [], 'Admin.Shopparameters.Feature', $locale);
            case self::Wednesday():
                return $translator->trans('Wednesday', [], 'Admin.Shopparameters.Feature', $locale);
            case self::Thursday():
                return $translator->trans('Thursday', [], 'Admin.Shopparameters.Feature', $locale);
            case self::Friday():
                return $translator->trans('Friday', [], 'Admin.Shopparameters.Feature', $locale);
            case self::Saturday():
                return $translator->trans('Saturday', [], 'Admin.Shopparameters.Feature', $locale);
            case self::Sunday():
                return $translator->trans('Sunday', [], 'Admin.Shopparameters.Feature', $locale);
            default:
                throw new \LogicException('Unreachable statement.');
        }
    }
}
