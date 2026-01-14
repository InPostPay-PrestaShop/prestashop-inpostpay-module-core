<?php

declare(strict_types=1);

namespace izi\prestashop\Common\Delivery;

use izi\prestashop\Enum\NotAnEnum;
use izi\prestashop\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @todo: Refactor. Not an enum, custom codes are allowed.
 *
 * @method static self Cod() cash on delivery option
 * @method static self Pww() weekend delivery option
 * @method static self Gw() gift wrapping option
 */
final class ServiceCode extends NotAnEnum implements TranslatableInterface
{
    private const COD = 'COD';
    private const PWW = 'PWW';
    private const GW = 'GW';

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
            return \count($c1) - \count($c2);
        });

        return $combinations;
    }

    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        switch ($this) {
            case self::Cod():
                return $translator->trans('Cash on Delivery', [], 'Modules.Inpostizi.Payment', $locale);
            case self::Pww():
                return $translator->trans('Weekend Delivery', [], 'Modules.Inpostizi.Delivery', $locale);
            case self::Gw():
                return trim($translator->trans('I would like my order to be gift wrapped %cost%', ['%cost%' => ''], 'Shop.Theme.Checkout', $locale));
            default:
                return $this->name;
        }
    }
}
