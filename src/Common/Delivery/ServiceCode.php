<?php

declare(strict_types=1);

namespace izi\prestashop\Common\Delivery;

use izi\prestashop\Enum\StringEnum;

/**
 * @method static self Cod() cash on delivery option
 * @method static self Pww() weekend delivery option
 */
final class ServiceCode extends StringEnum
{
    private const COD = 'COD';
    private const PWW = 'PWW';

    public function isAvailabilityTimeDependent(): bool
    {
        return self::Pww() === $this;
    }

    /**
     * @return array<self[]>
     */
    public static function getAvailableCombinations(DeliveryType $deliveryType): array
    {
        $serviceCodes = $deliveryType->getAvailableServiceCodes();
        $combinations = [[]];

        foreach ($serviceCodes as $serviceCode) {
            foreach ($combinations as $combination) {
                $combinations[] = array_merge($combination, [$serviceCode]);
            }
        }

        usort($combinations, static function (array $c1, $c2): int {
            return count($c1) - count($c2);
        });

        return $combinations;
    }
}
