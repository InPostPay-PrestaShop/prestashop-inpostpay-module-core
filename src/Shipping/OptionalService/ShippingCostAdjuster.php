<?php

declare(strict_types=1);

namespace izi\prestashop\Shipping\OptionalService;

use izi\prestashop\Common\Delivery\DeliveryType;
use izi\prestashop\Common\Delivery\ServiceCode;
use izi\prestashop\Configuration\PrestaShopConfiguration;
use izi\prestashop\Configuration\ShippingConfigurationInterface;
use izi\prestashop\Event\ValidateOrderEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ShippingCostAdjuster implements EventSubscriberInterface
{
    /**
     * @var \Context
     */
    private $context;

    /**
     * @var ShippingConfigurationInterface
     */
    private $shippingConfiguration;

    /**
     * @var PrestaShopConfiguration
     */
    private $shopConfiguration;

    /**
     * @var float|null
     */
    private $originalHandlingCost;

    /**
     * @var float|null
     */
    private $handlingCost;

    /**
     * @var bool
     */
    private $cacheCleared = false;

    public function __construct(\Context $context, ShippingConfigurationInterface $shippingConfiguration, PrestaShopConfiguration $shopConfiguration)
    {
        $this->context = $context;
        $this->shippingConfiguration = $shippingConfiguration;
        $this->shopConfiguration = $shopConfiguration;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ValidateOrderEvent::class => 'onOrderValidated',
        ];
    }

    public function addServiceCost(DeliveryType $deliveryType, string $serviceCode): void
    {
        if (0. === $serviceCost = $this->getServiceOptionCost($deliveryType, $serviceCode)) {
            return;
        }

        if (null === $this->handlingCost) {
            $this->handlingCost = $this->originalHandlingCost = $this->shopConfiguration->getShippingHandlingCost();
        }

        $this->handlingCost += $serviceCost;
        \Configuration::set(PrestaShopConfiguration::SHIPPING_HANDLING_COST, $this->handlingCost);

        $this->clearCartCache();
    }

    public function onOrderValidated(): void
    {
        if (!isset($this->originalHandlingCost)) {
            return;
        }

        \Configuration::set(PrestaShopConfiguration::SHIPPING_HANDLING_COST, $this->originalHandlingCost);
        $this->cacheCleared = false;
        $this->handlingCost = $this->originalHandlingCost = null;
    }

    private function getServiceOptionCost(DeliveryType $deliveryType, string $serviceCode): float
    {
        $serviceOptions = $this->shippingConfiguration
            ->getShippingOptions($deliveryType)
            ->getServiceOptions(ServiceCode::from($serviceCode));

        if (null === $serviceOptions || 0. === (float) $costPln = $serviceOptions->getAdditionalCost()) {
            return 0.;
        }

        $defaultCurrency = \Currency::getDefaultCurrency();
        if ('PLN' === $defaultCurrency->iso_code) {
            return $costPln;
        }

        return \Tools::convertPriceFull($costPln, $this->context->currency, $defaultCurrency);
    }

    private function clearCartCache(): void
    {
        if ($this->cacheCleared) {
            return;
        }

        \Cache::clean('getPackageShippingCost_*');
        \Cart::resetStaticCache();

        $this->cacheCleared = true;
    }
}
