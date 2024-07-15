<?php

declare(strict_types=1);

namespace izi\prestashop\Command\Config;

use izi\prestashop\Configuration\ApiConfigurationInterface;
use izi\prestashop\Configuration\GeneralConfigurationInterface;
use izi\prestashop\Configuration\OrdersConfigurationInterface;
use izi\prestashop\Configuration\ProductConfigurationInterface;
use izi\prestashop\Handler\Config\UpdateGeneralConfigurationHandler;
use izi\prestashop\Validator\InPostApiCredentials;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @see UpdateGeneralConfigurationHandler
 */
final class UpdateGeneralConfigurationCommand
{
    /**
     * @var ApiConfigurationInterface
     *
     * @Assert\Valid()
     *
     * @InPostApiCredentials(groups={"API"})
     */
    private $apiConfiguration;

    /**
     * @var OrdersConfigurationInterface
     *
     * @Assert\Valid()
     */
    private $ordersConfiguration;

    /**
     * @var GeneralConfigurationInterface
     *
     * @Assert\Valid()
     */
    private $generalConfiguration;

    /**
     * @var ProductConfigurationInterface
     *
     * @Assert\Valid()
     */
    private $productConfiguration;

    public function __construct(
        ApiConfigurationInterface $apiConfiguration,
        OrdersConfigurationInterface $ordersConfiguration,
        GeneralConfigurationInterface $generalConfiguration,
        ProductConfigurationInterface $productConfiguration
    ) {
        $this->apiConfiguration = $apiConfiguration;
        $this->ordersConfiguration = $ordersConfiguration;
        $this->generalConfiguration = $generalConfiguration;
        $this->productConfiguration = $productConfiguration;
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

    public function getGeneralConfiguration(): GeneralConfigurationInterface
    {
        return $this->generalConfiguration;
    }

    public function setGeneralConfiguration(GeneralConfigurationInterface $generalConfiguration): self
    {
        $this->generalConfiguration = $generalConfiguration;

        return $this;
    }

    public function getProductConfiguration(): ProductConfigurationInterface
    {
        return $this->productConfiguration;
    }

    public function setProductConfiguration(ProductConfigurationInterface $productConfiguration): self
    {
        $this->productConfiguration = $productConfiguration;

        return $this;
    }
}
