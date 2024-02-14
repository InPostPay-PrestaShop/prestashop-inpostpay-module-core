<?php

declare(strict_types=1);

namespace izi\prestashop\Handler\Config\Status;

use izi\prestashop\Configuration\DTO\Shipping;
use izi\prestashop\Configuration\ShippingConfigurationInterface;
use izi\prestashop\ObjectModel\Repository\CarrierRepository;
use izi\prestashop\ObjectModel\Repository\ObjectRepositoryInterface;

final class DeliveryOptionsStatusChecker implements StatusCheckerInterface
{
    private const TRANSLATION_SOURCE = 'deliveryoptionsstatuschecker';

    /**
     * @var \Module
     */
    private $module;

    /**
     * @var ObjectRepositoryInterface
     */
    private $carrierRepository;

    /**
     * @var ShippingConfigurationInterface
     */
    private $configuration;

    /**
     * @param CarrierRepository $carrierRepository
     */
    public function __construct(\Module $module, ObjectRepositoryInterface $carrierRepository, ShippingConfigurationInterface $configuration)
    {
        $this->module = $module;
        $this->carrierRepository = $carrierRepository;
        $this->configuration = $configuration;
    }

    public function checkStatus(): array
    {
        if ($this->isDeliveryOptionAvailable($this->configuration->getApmShippingOptions())) {
            return [];
        }

        if ($this->isDeliveryOptionAvailable($this->configuration->getCourierShippingOptions())) {
            return [];
        }

        return [$this->module->l('No delivery option is available.', self::TRANSLATION_SOURCE)];
    }

    private function isDeliveryOptionAvailable(Shipping $options): bool
    {
        if (null === $referenceId = $options->getCarrierId()) {
            return false;
        }

        if (null === $carrier = $this->carrierRepository->findOneByReferenceId($referenceId)) {
            return false;
        }

        return $carrier->active && $carrier->isAssociatedToShop();
    }
}
