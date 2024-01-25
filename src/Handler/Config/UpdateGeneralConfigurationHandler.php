<?php

declare(strict_types=1);

namespace izi\prestashop\Handler\Config;

use izi\prestashop\Command\Config\UpdateGeneralConfigurationCommand;
use izi\prestashop\Configuration\ApiConfiguration;
use izi\prestashop\Configuration\ApiConfigurationInterface;
use izi\prestashop\Configuration\OrdersConfiguration;
use izi\prestashop\Configuration\OrdersConfigurationInterface;
use Psr\SimpleCache\CacheInterface;

final class UpdateGeneralConfigurationHandler implements UpdateGeneralConfigurationHandlerInterface
{
    /**
     * @var ApiConfigurationInterface
     */
    private $apiConfiguration;

    /**
     * @var OrdersConfigurationInterface
     */
    private $ordersConfiguration;

    /**
     * @var CacheInterface
     */
    private $cache;

    /**
     * @param ApiConfiguration $apiConfiguration
     * @param OrdersConfiguration $ordersConfiguration
     */
    public function __construct(ApiConfigurationInterface $apiConfiguration, OrdersConfigurationInterface $ordersConfiguration, CacheInterface $cache)
    {
        $this->apiConfiguration = $apiConfiguration;
        $this->ordersConfiguration = $ordersConfiguration;
        $this->cache = $cache;
    }

    public static function getHandledCommandClass(): string
    {
        return UpdateGeneralConfigurationCommand::class;
    }

    public function __invoke(UpdateGeneralConfigurationCommand $command)
    {
        $config = $command->getConfiguration();

        $this->apiConfiguration->persist($config->getApiConfiguration());
        $this->ordersConfiguration->persist($config->getOrdersConfiguration());

        $this->cache->clear();
    }
}
