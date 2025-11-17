<?php

declare(strict_types=1);

namespace izi\prestashop\Product\Restriction;

use izi\prestashop\Common\Delivery\DeliveryType;
use izi\prestashop\Enum\IntEnum;
use izi\prestashop\Translation\LegacyTranslator;
use izi\prestashop\Translation\TranslatableInterface;

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

    public function trans(LegacyTranslator $translator): string
    {
        switch ($this) {
            case self::HideWidget():
                return $translator->l('Do not display widget', 'restrictedaction');
            case self::DisallowOrder():
                return $translator->l('Disallow order', 'restrictedaction');
            case self::DisallowCourierDelivery():
                return $translator->l('Disallow courier delivery', 'restrictedaction');
            case self::DisallowApmDelivery():
                return $translator->l('Disallow APM delivery', 'restrictedaction');
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
