<?php

declare(strict_types=1);

namespace izi\prestashop\Common\Delivery;

use izi\prestashop\Enum\StringEnum;
use izi\prestashop\Translation\LegacyTranslator;

/**
 * @method static self Apm()
 * @method static self Courier()
 */
final class DeliveryType extends StringEnum
{
    private const APM = 'APM';
    private const COURIER = 'COURIER';

    public function trans(LegacyTranslator $translator): string
    {
        switch ($this) {
            case self::Apm():
                return $translator->l('APM', 'deliverytype');
            case self::Courier():
                return $translator->l('Courier', 'deliverytype');
            default:
                return $this->name;
        }
    }

    /**
     * @return ServiceCode[]
     */
    public function getAvailableServiceCodes(): array
    {
        if (self::Apm() === $this) {
            return ServiceCode::cases();
        }

        return [ServiceCode::Cod()];
    }
}
