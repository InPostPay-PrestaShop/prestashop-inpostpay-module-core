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
     * @var GeneralConfigurationInterface
     */
    private $generalConfiguration;

    /**
     * @var CacheInterface
     */
    private $cache;

    /**
     * @param ApiConfiguration $apiConfiguration
     * @param OrdersConfiguration $ordersConfiguration
     * @param GeneralConfiguration $generalConfiguration
     */
    public function __construct(ApiConfigurationInterface $apiConfiguration, OrdersConfigurationInterface $ordersConfiguration, GeneralConfigurationInterface $generalConfiguration, CacheInterface $cache)
    {
        $this->apiConfiguration = $apiConfiguration;
        $this->ordersConfiguration = $ordersConfiguration;
        $this->generalConfiguration = $generalConfiguration;
        $this->cache = $cache;
    }

    public static function getHandledCommandClass(): string
    {
        return UpdateGeneralConfigurationCommand::class;
    }

    public function __invoke(UpdateGeneralConfigurationCommand $command)
    {
        $this->apiConfiguration->persist($command->getApiConfiguration());
        $this->ordersConfiguration->persist($command->getOrdersConfiguration());
        $this->generalConfiguration->persist($command->getGeneralConfiguration());

        $this->cache->clear();
    }
}
