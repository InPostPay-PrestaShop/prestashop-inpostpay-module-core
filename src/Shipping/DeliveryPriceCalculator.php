<?php

declare(strict_types=1);

namespace izi\prestashop\Shipping;

use izi\prestashop\Builder\PriceFactory;
use izi\prestashop\Common\Price;
use izi\prestashop\Configuration\DTO\Shipping\ServiceOptions;
use izi\prestashop\Configuration\PrestaShopConfiguration;
use izi\prestashop\Currency\PriceConverterInterface;
use izi\prestashop\ObjectModel\Repository\ObjectRepositoryInterface;
use izi\prestashop\Shipping\Exception\UnavailableDeliveryOptionException;

final class DeliveryPriceCalculator implements DeliveryPriceCalculatorInterface
{
    /**
     * @var PrestaShopConfiguration
     */
    private $configuration;

    /**
     * @var ObjectRepositoryInterface<\Address>
     */
    private $addressRepository;

    /**
     * @var PriceConverterInterface
     */
    private $priceConverter;

    /**
     * @var FreeDelivery\MinAmountCalculationStrategyInterface
     */
    private $freeDeliveryStrategy;

    /**
     * @param ObjectRepositoryInterface<\Address> $addressRepository
     */
    public function __construct(PrestaShopConfiguration $configuration, ObjectRepositoryInterface $addressRepository, PriceConverterInterface $priceConverter, FreeDelivery\MinAmountCalculationStrategyInterface $freeDeliveryStrategy)
    {
        $this->configuration = $configuration;
        $this->addressRepository = $addressRepository;
        $this->priceConverter = $priceConverter;
        $this->freeDeliveryStrategy = $freeDeliveryStrategy;
    }

    public function getDeliveryPrice(\Cart $cart, \Carrier $carrier, ?array $productList = null): Price
    {
        $carrierId = (int) $carrier->id;

        if (false === $netAmount = $cart->getPackageShippingCost($carrierId, false, null, $productList)) {
            throw UnavailableDeliveryOptionException::for($carrier);
        }

        $grossAmount = (float) $cart->getPackageShippingCost($carrierId, true, null, $productList);

        return PriceFactory::create((float) $netAmount, $grossAmount);
    }

    public function getAdditionalServicePrice(\Cart $cart, \Carrier $carrier, ServiceOptions $options): Price
    {
        if (!$carrier->shipping_handling) {
            return PriceFactory::create(0., 0.);
        }

        if (0. === $netAmount = (float) $options->getAdditionalCost()) {
            return PriceFactory::create(0., 0.);
        }

        $grossAmount = $this->applyTaxes($cart, $carrier, $netAmount);

        return PriceFactory::create($netAmount, $grossAmount);
    }

    public function getFreeDeliveryMinAmount(\Cart $cart, \Carrier $carrier): ?float
    {
        $amount = $this->freeDeliveryStrategy->getMinAmount($cart, $carrier);

        if (null === $amount || 0. === $amount) {
            return $amount;
        }

        $amount = $this->priceConverter->convertByIds($amount, (int) $cart->id_currency);

        return \Tools::ps_round($amount, 2);
    }

    private function applyTaxes(\Cart $cart, \Carrier $carrier, float $netAmount): float
    {
        if (0. === $netAmount) {
            return 0.;
        }

        $address = $this->getTaxAddress($cart);

        return $carrier
            ->getTaxCalculator($address)
            ->addTaxes($netAmount);
    }

    private function getTaxAddress(\Cart $cart): \Address
    {
        $taxAddressType = $this->configuration->getTaxAddressType((int) $cart->id_shop);
        $addressId = (int) $cart->$taxAddressType;

        return $this->addressRepository->find($addressId) ?? \Address::initialize();
    }
}
