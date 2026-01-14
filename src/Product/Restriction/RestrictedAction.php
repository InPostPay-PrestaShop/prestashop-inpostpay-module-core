<?php

declare(strict_types=1);

namespace izi\prestashop\Product\Restriction;

use izi\prestashop\Common\Delivery\DeliveryType;
use izi\prestashop\Enum\IntEnum;
use izi\prestashop\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @method static self HideWidget()
 * @method static self DisallowOrder()
 * @method static self DisallowCourierDelivery()
 * @method static self DisallowApmDelivery()
 */
final class RestrictedAction extends IntEnum implements TranslatableInterface
{
    private const HIDE_WIDGET = 0;
    private const DISALLOW_ORDER = 1;
    private const DISALLOW_COURIER_DELIVERY = 2;
    private const DISALLOW_APM_DELIVERY = 3;

    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        switch ($this) {
            case self::HideWidget():
                return $translator->trans('Do not display widget', [], 'Modules.Inpostizi.Product', $locale);
            case self::DisallowOrder():
                return $translator->trans('Disallow order', [], 'Modules.Inpostizi.Product', $locale);
            case self::DisallowCourierDelivery():
                return $translator->trans('Disallow courier delivery', [], 'Modules.Inpostizi.Product', $locale);
            case self::DisallowApmDelivery():
                return $translator->trans('Disallow APM delivery', [], 'Modules.Inpostizi.Product', $locale);
            default:
                throw new \LogicException('Not implemented.');
        }
    }

    public function hidesWidget(): bool
    {
        switch ($this) {
            case self::HideWidget():
            case self::DisallowOrder():
                return true;
            default:
                return false;
        }
    }

    public function appliesTo(?DeliveryType $deliveryType = null, bool $strict = false): bool
    {
        switch ($this) {
            case self::DisallowCourierDelivery():
                return DeliveryType::Courier() === $deliveryType;
            case self::DisallowApmDelivery():
                return DeliveryType::Apm() === $deliveryType;
            case self::DisallowOrder():
                return !$strict || null === $deliveryType;
            default:
                return false;
        }
    }
}
