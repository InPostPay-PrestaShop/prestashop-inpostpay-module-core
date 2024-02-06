<?php

declare(strict_types=1);

namespace izi\prestashop\Configuration;


use izi\prestashop\Configuration\DTO\Day;
use izi\prestashop\Configuration\DTO\Hour;
use izi\prestashop\Configuration\Factory\DayFactoryInterface;
use izi\prestashop\Configuration\Factory\HourFactoryInterface;
use izi\prestashop\ObjectModel\Repository\ObjectRepositoryInterface;

trait ShippingConfigurationTrait
{
    /**
     * @var DayFactoryInterface
     */
    private $dayFactory;

    /**
     * @var HourFactoryInterface
     */
    private $hourFactory;

    /**
     * @var ObjectRepositoryInterface
     */
    private $carrierRepository;

    private function loadDay(?int $id): ?Day
    {
        if (null === $id) {
            return null;
        }

        try {
            return $this->dayFactory->create($id);
        } catch (\InvalidArgumentException $e) {
            return null;
        }
    }

    private function loadHour(?int $id): ?Hour
    {
        if (null === $id) {
            return null;
        }

        try {
            return $this->hourFactory->create($id);
        } catch (\InvalidArgumentException $e) {
            return null;
        }
    }

    private function loadCarrier(int $idReference): ?\Carrier
    {
        return $this->carrierRepository->findOneBy(['id_reference' => $idReference]);
    }
}
