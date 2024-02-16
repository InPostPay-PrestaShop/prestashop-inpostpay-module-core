<?php

declare(strict_types=1);

namespace izi\prestashop\Form\Provider;

use izi\prestashop\Configuration\ConfigurationInterface;
use izi\prestashop\Configuration\ShopAwareConfigurationInterface;

class DefaultCurrencyProvider implements DefaultCurrencyProviderInterface
{
    const DEFAULT_CURRENCY_CONFIGURATION_KEY = 'PS_CURRENCY_DEFAULT';

    /**
     * @var ConfigurationInterface
     */
    private $configuration;

    public function __construct(ShopAwareConfigurationInterface $configuration)
    {
        $this->configuration = $configuration;
    }

    public function getDefaultCurrency(): \Currency
    {
        $idCurrency = (int) $this->configuration->get(self::DEFAULT_CURRENCY_CONFIGURATION_KEY);

        return new \Currency($idCurrency);
    }
}
