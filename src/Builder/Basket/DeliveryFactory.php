<?php

declare(strict_types=1);

namespace izi\prestashop\Builder\Basket;

use izi\prestashop\Builder\PriceFactory;
use izi\prestashop\Common\Basket\DeliveryOption;
use izi\prestashop\Common\Delivery\DeliveryType;
use izi\prestashop\Common\Delivery\OptionalService;
use izi\prestashop\Common\Delivery\ServiceCode;
use izi\prestashop\Common\Price;
use izi\prestashop\Configuration\DTO\Shipping\ServiceOptions;
use izi\prestashop\Configuration\DTO\Shipping\ShippingOptions;
use izi\prestashop\Configuration\ShippingConfigurationInterface;
use izi\prestashop\ObjectModel\Repository\CarrierRepository;
use izi\prestashop\ObjectModel\Repository\ObjectRepositoryInterface;
use izi\prestashop\Shipping\DeliveryPriceCalculatorInterface;
use izi\prestashop\Translation\ServiceNameTranslator;
use Psr\Clock\ClockInterface;

class DeliveryFactory
{
    /**
     * @var ShippingConfigurationInterface
     */
    private $configuration;

    /**
     * @var CarrierRepository
     */
    private $carrierRepository;

    /**
     * @var ClockInterface
     */
    private $clock;

    /**
     * @var ServiceNameTranslator
     */
    private $serviceNameTranslator;

    /**
     * @var DeliveryPriceCalculatorInterface
     */
    private $priceCalculator;

    /**
     * @param CarrierRepository $carrierRepository
     */
    public function __construct(ShippingConfigurationInterface $configuration, ObjectRepositoryInterface $carrierRepository, ClockInterface $clock, ServiceNameTranslator $serviceNameTranslator, DeliveryPriceCalculatorInterface $priceCalculator)
    {
        $this->configuration = $configuration;
        $this->carrierRepository = $carrierRepository;
        $this->clock = $clock;
        $this->serviceNameTranslator = $serviceNameTranslator;
        $this->priceCalculator = $priceCalculator;
    }

    /**
     * @return DeliveryOption[]
     */
    public function getAvailableDeliveryOptions(\Cart $cart): array
    {
        $deliveryOptions = [];

        $deliveryDate = $this->getDeliveryDate();
        $isFreeShipping = null;

        foreach (DeliveryType::cases() as $deliveryType) {
            $options = $this->configuration->getShippingOptions($deliveryType, (int) $cart->id_shop);
            $referenceId = $options->getCarrierMapping()->getReferenceId();

            if (null === $referenceId || null === $carrier = $this->getCarrier($cart, $referenceId)) {
                continue;
            }

            if (!isset($isFreeShipping)) {
                $isFreeShipping = $this->hasFreeShippingCartRule($cart);
            }

            $price = $isFreeShipping
                ? PriceFactory::create(0., 0.)
                : $this->priceCalculator->getDeliveryPrice($cart, $carrier);

            $deliveryOptions[] = new DeliveryOption(
                $deliveryType,
                $deliveryDate,
                $price,
                $this->getOptionalServices($deliveryType, $cart, $options, $carrier, $isFreeShipping),
                $this->priceCalculator->getFreeDeliveryMinAmount($cart, $carrier)
            );
        }

        return $deliveryOptions;
    }

    /**
     * @return OptionalService[]
     */
    private function getOptionalServices(DeliveryType $deliveryType, \Cart $cart, ShippingOptions $options, \Carrier $defaultCarrier, bool $isFreeShipping): array
    {
        $services = [];

        foreach ($deliveryType->getAvailableServiceCodes() as $serviceCode) {
            if (null === $carrierId = $options->getCarrierMapping($serviceCode)->getReferenceId()) {
                continue;
            }

            $serviceOptions = $options->getServiceOptions($serviceCode);

            if (null === $serviceOptions || !$this->checkServiceAvailability($serviceCode, $serviceOptions)) {
                continue;
            }

            if ($carrierId === (int) $defaultCarrier->id_reference) {
                $carrier = $defaultCarrier;
            } elseif (null === $carrier = $this->getCarrier($cart, $carrierId)) {
                continue;
            }

            $servicePrice = $this->getServicePrice($serviceOptions, $cart, $carrier, $defaultCarrier, $isFreeShipping);

            if (0 > $servicePrice->getNet()) {
                continue;
            }

            $services[] = new OptionalService(
                $this->serviceNameTranslator->getName($serviceCode),
                $serviceCode,
                $servicePrice
            );
        }

        return $services;
    }

    private function getServicePrice(ServiceOptions $options, \Cart $cart, \Carrier $carrier, \Carrier $defaultCarrier, bool $isFreeShipping): Price
    {
        if ($isFreeShipping) {
            return PriceFactory::create(0., 0.);
        }

        $servicePrice = $this->priceCalculator->getAdditionalServicePrice($cart, $carrier, $options);

        if ($carrier === $defaultCarrier) {
            return $servicePrice;
        }

        $carrierPrice = $this->priceCalculator->getDeliveryPrice($cart, $carrier);
        $defaultCarrierPrice = $this->priceCalculator->getDeliveryPrice($cart, $defaultCarrier);

        return $servicePrice
            ->add($carrierPrice)
            ->sub($defaultCarrierPrice);
    }

    private function checkServiceAvailability(ServiceCode $serviceCode, ServiceOptions $options): bool
    {
        if (!$serviceCode->isAvailabilityTimeDependent()) {
            return true;
        }

        $availability = $options->getAvailabilityRange();

        return null === $availability
            || $availability->contains($this->clock->now());
    }

    private function isDeliveryOptionAvailable(\Cart $cart, int $carrierId): bool
    {
        $deliveryOptionList = $cart->getDeliveryOptionList();
        $addressId = (int) $cart->id_address_delivery;

        if (!isset($deliveryOptionList[$addressId])) {
            return false;
        }

        foreach ($deliveryOptionList[$addressId] as $option) {
            if (isset($option['carrier_list'][$carrierId]) && 1 === count($option['carrier_list'])) {
                return true;
            }
        }

        return false;
    }

    private function hasFreeShippingCartRule(\Cart $cart): bool
    {
        return [] !== $cart->getCartRules(\CartRule::FILTER_ACTION_SHIPPING, false);
    }

    private function getCarrier(\Cart $cart, int $referenceId): ?\Carrier
    {
        $carrier = $this->carrierRepository->findOneByReferenceId($referenceId);

        if (null === $carrier || !$this->isDeliveryOptionAvailable($cart, (int) $carrier->id)) {
            return null;
        }

        return $carrier;
    }

    // TODO make configurable?
    private function getDeliveryDate(): \DateTimeImmutable
    {
        return $this->clock
            ->now()
            ->modify('+2 days')
            ->setTime(12, 0);
    }
}
