<?php

declare(strict_types=1);

namespace izi\prestashop\Common\Delivery;

use izi\prestashop\Enum\StringEnum;
use izi\prestashop\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @method static self Apm()
 * @method static self Courier()
 * @method static self Digital()
 */
final class DeliveryType extends StringEnum implements TranslatableInterface
{
    private const APM = 'APM';
    private const COURIER = 'COURIER';
    private const DIGITAL = 'DIGITAL';

    public static function getPhysicalDeliveryTypes(): array
    {
        return [self::Apm(), self::Courier()];
    }

    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        switch ($this) {
            case self::Apm():
                return $translator->trans('APM', [], 'Modules.Inpostizi.Delivery', $locale);
            case self::Courier():
                return $translator->trans('Courier', [], 'Modules.Inpostizi.Delivery', $locale);
            case self::Digital():
                return $translator->trans('Digital delivery', [], 'Modules.Inpostizi.Delivery', $locale);
            default:
                return $this->name;
        }
    }

    /**
     * @return ServiceCode[]
     */
    public function getAvailableServiceCodes(): array
    {
        if (self::Digital() === $this) {
            return [];
        }

        if (self::Apm() === $this) {
            return [
                ServiceCode::Cod(),
                ServiceCode::Pww(),
            ];
        }

        return [ServiceCode::Cod()];
    }
}
