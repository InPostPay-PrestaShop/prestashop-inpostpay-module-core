<?php

declare(strict_types=1);

namespace izi\prestashop;

use izi\prestashop\Analytics\Command\UpdateCartAnalyticsCommand;
use izi\prestashop\Analytics\Handler\UpdateCartAnalyticsHandlerInterface;
use izi\prestashop\Command\Config\CheckStatusCommand;
use izi\prestashop\Command\Config\DownloadModuleDataCommand;
use izi\prestashop\Command\Config\UpdateAdvancedConfigurationCommand;
use izi\prestashop\Command\Config\UpdateCartRuleOptionsCommand;
use izi\prestashop\Command\Config\UpdateConsentsConfigurationCommand;
use izi\prestashop\Command\Config\UpdateGeneralConfigurationCommand;
use izi\prestashop\Command\Config\UpdateGuiConfigurationCommand;
use izi\prestashop\Command\Config\UpdateShippingConfigurationCommand;
use izi\prestashop\Command\GetBasketBindingKeyCommand;
use izi\prestashop\Command\GetOrderConfirmationUrlCommand;
use izi\prestashop\Command\UnbindBasketCommand;
use izi\prestashop\Command\UpdateBasketCommand;
use izi\prestashop\Command\UpdateOrderAddressDeliveryCommand;
use izi\prestashop\Command\UpdateOrderStatusCommand;
use izi\prestashop\Command\UpdateOrderTrackingNumbersCommand;
use izi\prestashop\DependencyInjection\ServiceSubscriberInterface;
use izi\prestashop\Extension\Message\InstallExtensionCommand;
use izi\prestashop\Extension\MessageHandler\InstallExtensionHandler;
use izi\prestashop\Handler\Config\CheckStatusHandlerInterface;
use izi\prestashop\Handler\Config\DownloadModuleDataHandlerInterface;
use izi\prestashop\Handler\Config\UpdateAdvancedConfigurationHandlerInterface;
use izi\prestashop\Handler\Config\UpdateCartRuleOptionsHandlerInterface;
use izi\prestashop\Handler\Config\UpdateConsentsConfigurationHandlerInterface;
use izi\prestashop\Handler\Config\UpdateGeneralConfigurationHandlerInterface;
use izi\prestashop\Handler\Config\UpdateGuiConfigurationHandlerInterface;
use izi\prestashop\Handler\Config\UpdateShippingConfigurationHandlerInterface;
use izi\prestashop\Handler\GetBasketBindingKeyHandlerInterface;
use izi\prestashop\Handler\GetOrderConfirmationUrlHandlerInterface;
use izi\prestashop\Handler\UnbindBasketHandlerInterface;
use izi\prestashop\Handler\UpdateBasketHandlerInterface;
use izi\prestashop\Handler\UpdateOrderAddressDeliveryHandlerInterface;
use izi\prestashop\Handler\UpdateOrderStatusHandlerInterface;
use izi\prestashop\Handler\UpdateOrderTrackingNumbersHandlerInterface;
use izi\prestashop\HotProduct\Message\CreateHotProductCommand;
use izi\prestashop\HotProduct\Message\DeleteHotProductCommand;
use izi\prestashop\HotProduct\Message\DeleteRemoteProductCommand;
use izi\prestashop\HotProduct\Message\ImportHotProductCommand;
use izi\prestashop\HotProduct\Message\UpdateHotProductCommand;
use izi\prestashop\HotProduct\MessageHandler\CreateHotProductHandlerInterface;
use izi\prestashop\HotProduct\MessageHandler\DeleteHotProductHandlerInterface;
use izi\prestashop\HotProduct\MessageHandler\DeleteRemoteProductHandlerInterface;
use izi\prestashop\HotProduct\MessageHandler\ImportHotProductHandlerInterface;
use izi\prestashop\HotProduct\MessageHandler\UpdateHotProductHandlerInterface;
use izi\prestashop\MerchantApi\Command\AddProductToBasketCommand;
use izi\prestashop\MerchantApi\Command\Basket\AddProductToCartCommand;
use izi\prestashop\MerchantApi\Command\Basket\CreateCartCommand;
use izi\prestashop\MerchantApi\Command\Basket\IncrementCartQuantityCommand;
use izi\prestashop\MerchantApi\Command\ConfirmBasketBindingCommand;
use izi\prestashop\MerchantApi\Command\CreateOrderCommand;
use izi\prestashop\MerchantApi\Command\DeleteBasketBindingCommand;
use izi\prestashop\MerchantApi\Command\GetBasketCommand;
use izi\prestashop\MerchantApi\Command\GetOrderCommand;
use izi\prestashop\MerchantApi\Command\GetProductsCommand;
use izi\prestashop\MerchantApi\Command\Order\UpdateCartMessageCommand;
use izi\prestashop\MerchantApi\Command\UpdateOrderCommand;
use izi\prestashop\MerchantApi\Handler\AddProductToBasketHandlerInterface;
use izi\prestashop\MerchantApi\Handler\Basket\AddProductToCartHandlerInterface;
use izi\prestashop\MerchantApi\Handler\Basket\CreateCartHandlerInterface;
use izi\prestashop\MerchantApi\Handler\Basket\IncrementCartQuantityHandlerInterface;
use izi\prestashop\MerchantApi\Handler\ConfirmBasketBindingHandlerInterface;
use izi\prestashop\MerchantApi\Handler\CreateOrderHandlerInterface;
use izi\prestashop\MerchantApi\Handler\DeleteBasketBindingHandlerInterface;
use izi\prestashop\MerchantApi\Handler\GetBasketHandlerInterface;
use izi\prestashop\MerchantApi\Handler\GetOrderHandlerInterface;
use izi\prestashop\MerchantApi\Handler\GetProductsHandlerInterface;
use izi\prestashop\MerchantApi\Handler\Order\UpdateCartMessageHandlerInterface;
use izi\prestashop\MerchantApi\Handler\UpdateOrderHandlerInterface;
use izi\prestashop\ProductOptions\Message\UpdateProductOptionsCommand;
use izi\prestashop\ProductOptions\MessageHandler\UpdateProductOptionsHandlerInterface;
use Psr\Container\ContainerInterface;

final class CommandBus implements CommandBusInterface, ServiceSubscriberInterface
{
    /**
     * @var ContainerInterface
     */
    private $locator;

    public function __construct(ContainerInterface $locator)
    {
        $this->locator = $locator;
    }

    public static function getSubscribedServices(): array
    {
        return [
            UpdateOrderStatusCommand::class => UpdateOrderStatusHandlerInterface::class,
            UpdateOrderAddressDeliveryCommand::class => UpdateOrderAddressDeliveryHandlerInterface::class,
            UpdateOrderTrackingNumbersCommand::class => UpdateOrderTrackingNumbersHandlerInterface::class,
            UpdateBasketCommand::class => UpdateBasketHandlerInterface::class,
            UnbindBasketCommand::class => UnbindBasketHandlerInterface::class,

            /* widget v2 */
            GetBasketBindingKeyCommand::class => '?' . GetBasketBindingKeyHandlerInterface::class,
            GetOrderConfirmationUrlCommand::class => '?' . GetOrderConfirmationUrlHandlerInterface::class,

            /* merchant API */
            ConfirmBasketBindingCommand::class => '?' . ConfirmBasketBindingHandlerInterface::class,
            DeleteBasketBindingCommand::class => '?' . DeleteBasketBindingHandlerInterface::class,
            GetBasketCommand::class => '?' . GetBasketHandlerInterface::class,
            MerchantApi\Command\UpdateBasketCommand::class => '?' . MerchantApi\Handler\UpdateBasketHandlerInterface::class,
            CreateOrderCommand::class => '?' . CreateOrderHandlerInterface::class,
            GetOrderCommand::class => '?' . GetOrderHandlerInterface::class,
            UpdateOrderCommand::class => '?' . UpdateOrderHandlerInterface::class,
            GetProductsCommand::class => '?' . GetProductsHandlerInterface::class,
            AddProductToBasketCommand::class => '?' . AddProductToBasketHandlerInterface::class,

            UpdateCartMessageCommand::class => '?' . UpdateCartMessageHandlerInterface::class,
            CreateCartCommand::class => '?' . CreateCartHandlerInterface::class,
            AddProductToCartCommand::class => '?' . AddProductToCartHandlerInterface::class,
            IncrementCartQuantityCommand::class => '?' . IncrementCartQuantityHandlerInterface::class,

            /* configuration */
            UpdateGeneralConfigurationCommand::class => '?' . UpdateGeneralConfigurationHandlerInterface::class,
            UpdateConsentsConfigurationCommand::class => '?' . UpdateConsentsConfigurationHandlerInterface::class,
            UpdateGuiConfigurationCommand::class => '?' . UpdateGuiConfigurationHandlerInterface::class,
            UpdateShippingConfigurationCommand::class => '?' . UpdateShippingConfigurationHandlerInterface::class,
            UpdateAdvancedConfigurationCommand::class => '?' . UpdateAdvancedConfigurationHandlerInterface::class,
            CheckStatusCommand::class => '?' . CheckStatusHandlerInterface::class,
            DownloadModuleDataCommand::class => '?' . DownloadModuleDataHandlerInterface::class,
            InstallExtensionCommand::class => '?' . InstallExtensionHandler::class,
            UpdateCartRuleOptionsCommand::class => '?' . UpdateCartRuleOptionsHandlerInterface::class,
            UpdateProductOptionsCommand::class => '?' . UpdateProductOptionsHandlerInterface::class,

            CreateHotProductCommand::class => '?' . CreateHotProductHandlerInterface::class,
            ImportHotProductCommand::class => '?' . ImportHotProductHandlerInterface::class,
            UpdateHotProductCommand::class => '?' . UpdateHotProductHandlerInterface::class,
            DeleteHotProductCommand::class => '?' . DeleteHotProductHandlerInterface::class,
            DeleteRemoteProductCommand::class => '?' . DeleteRemoteProductHandlerInterface::class,

            UpdateCartAnalyticsCommand::class => '?' . UpdateCartAnalyticsHandlerInterface::class,
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function handle($command)
    {
        $class = get_class($command);
        $handler = $this->locator->get($class);

        if (!is_callable($handler)) {
            throw new \LogicException(sprintf('Handler for command "%s" is not callable', $class));
        }

        return $handler($command);
    }
}
