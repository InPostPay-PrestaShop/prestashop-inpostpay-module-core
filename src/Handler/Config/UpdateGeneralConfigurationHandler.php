<?php

declare(strict_types=1);

namespace izi\prestashop\Handler\Config;

use izi\prestashop\Command\Config\UpdateGeneralConfigurationCommand;
use izi\prestashop\Configuration\ApiConfiguration;
use izi\prestashop\Configuration\ApiConfigurationInterface;
use izi\prestashop\Configuration\GeneralConfiguration;
use izi\prestashop\Configuration\GeneralConfigurationInterface;
use izi\prestashop\Configuration\OrdersConfiguration;
use izi\prestashop\Configuration\OrdersConfigurationInterface;
use izi\prestashop\Handler\CommandHandlerTrait;
use Psr\SimpleCache\CacheInterface;

final class UpdateGeneralConfigurationHandler implements UpdateGeneralConfigurationHandlerInterface
{
    use CommandHandlerTrait;

    /**
     * @var ApiConfigurationInterface
     */
    private $apiConfiguration;

    /**
     * @var OrdersConfigurationInterface
     */
    private $ordersConfiguration;

    /**
     * @var GeneralConfigurationInterface
     */
    private $generalConfiguration;

    /**
     * @var CacheInterface
     */
    private $cache;

    /**
     * @var \Module
     */
    private $module;

    /**
     * @param ApiConfiguration $apiConfiguration
     * @param OrdersConfiguration $ordersConfiguration
     * @param GeneralConfiguration $generalConfiguration
     * @param CacheInterface $cache
     * @param \Module $module
     */
    public function __construct(
        ApiConfigurationInterface $apiConfiguration,
        OrdersConfigurationInterface $ordersConfiguration,
        GeneralConfigurationInterface $generalConfiguration,
        CacheInterface $cache,
        $module
    ) {
        $this->apiConfiguration = $apiConfiguration;
        $this->ordersConfiguration = $ordersConfiguration;
        $this->generalConfiguration = $generalConfiguration;
        $this->cache = $cache;
        $this->module = $module;
    }

    public function __invoke(UpdateGeneralConfigurationCommand $command)
    {
        $this->apiConfiguration->persist($command->getApiConfiguration());
        $this->ordersConfiguration->persist($command->getOrdersConfiguration());
        $this->generalConfiguration->persist($command->getGeneralConfiguration());

        $this->module->registerHook($command->getGeneralConfiguration()->getProductCardDisplayHook());
        $this->module->registerHook($command->getGeneralConfiguration()->getCheckoutButtonDisplayHook());

        $this->cache->clear();
    }
}
