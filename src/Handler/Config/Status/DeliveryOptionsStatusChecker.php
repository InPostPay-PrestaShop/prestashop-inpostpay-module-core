<?php

declare(strict_types=1);

namespace izi\prestashop\Handler\Config\Status;

use izi\prestashop\Configuration\DTO\Shipping\ShippingOptions;
use izi\prestashop\Configuration\ShippingConfigurationInterface;
use izi\prestashop\ObjectModel\Repository\CarrierRepository;
use izi\prestashop\ObjectModel\Repository\ObjectRepositoryInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class DeliveryOptionsStatusChecker implements StatusCheckerInterface
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var CarrierRepository
     */
    private $carrierRepository;

    /**
     * @var ShippingConfigurationInterface
     */
    private $configuration;

    /**
     * @param CarrierRepository $carrierRepository
     */
    public function __construct(TranslatorInterface $translator, ObjectRepositoryInterface $carrierRepository, ShippingConfigurationInterface $configuration)
    {
        $this->translator = $translator;
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

        return [$this->translator->trans('No delivery option is available.', [], 'Modules.Inpostizi.Status')];
    }

    private function isDeliveryOptionAvailable(ShippingOptions $options): bool
    {
        if (null === $carrierId = $options->getCarrierMapping()->getReferenceId()) {
            return false;
        }

        if (null === $carrier = $this->carrierRepository->findOneByReferenceId($carrierId)) {
            return false;
        }

        return $carrier->active && $carrier->isAssociatedToShop();
    }
}
