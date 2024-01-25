<?php

declare(strict_types=1);

namespace izi\prestashop\Configuration\DTO;

use izi\prestashop\Configuration\ApiConfigurationInterface;
use izi\prestashop\Configuration\OrdersConfigurationInterface;
use izi\prestashop\Validator\InPostApiCredentials;
use Symfony\Component\Validator\Constraints as Assert;

final class GeneralConfiguration
{
    /**
     * @var ApiConfigurationInterface
     *
     * @Assert\Valid()
     * @InPostApiCredentials(groups={"API"})
     */
    private $apiConfiguration;

    /**
     * @var OrdersConfigurationInterface
     *
     * @Assert\Valid()
     */
    private $ordersConfiguration;

    public function __construct(ApiConfigurationInterface $apiConfiguration, OrdersConfigurationInterface $ordersConfiguration)
    {
        $this->apiConfiguration = $apiConfiguration;
        $this->ordersConfiguration = $ordersConfiguration;
    }

    public function getApiConfiguration(): ApiConfigurationInterface
    {
        return $this->apiConfiguration;
    }

    public function setApiConfiguration(ApiConfigurationInterface $apiConfiguration): self
    {
        $this->apiConfiguration = $apiConfiguration;

        return $this;
    }

    public function getOrdersConfiguration(): OrdersConfigurationInterface
    {
        return $this->ordersConfiguration;
    }

    public function setOrdersConfiguration(OrdersConfigurationInterface $ordersConfiguration): self
    {
        $this->ordersConfiguration = $ordersConfiguration;

        return $this;
    }
}
