<?php

declare(strict_types=1);

namespace izi\prestashop\Common\Delivery;

use izi\prestashop\Enum\StringEnum;

/**
 * @method static self Apm()
 * @method static self Courier()
 */
final class DeliveryType extends StringEnum
{
    private const APM = 'APM';
    private const COURIER = 'COURIER';

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
