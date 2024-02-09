<?php

declare(strict_types=1);

namespace izi\prestashop;

use izi\prestashop\Command\BindBasketCommand;
use izi\prestashop\Command\Config\UpdateAdvancedConfigurationCommand;
use izi\prestashop\Command\Config\UpdateConsentsConfigurationCommand;
use izi\prestashop\Command\Config\UpdateGeneralConfigurationCommand;
use izi\prestashop\Command\Config\UpdateGuiConfigurationCommand;
use izi\prestashop\Command\Config\UpdateShippingConfigurationCommand;
use izi\prestashop\Command\GenerateDeepLinkCommand;
use izi\prestashop\Command\GetBindingConfirmationCommand;
use izi\prestashop\Command\GetClientDetailsCommand;
use izi\prestashop\Command\GetOrderEventsCommand;
use izi\prestashop\Command\UnbindBasketCommand;
use izi\prestashop\Command\UpdateBasketCommand;
use izi\prestashop\Command\UpdateOrderTrackingNumbersCommand;
use izi\prestashop\Handler\BindBasketHandlerInterface;
use izi\prestashop\Handler\Config\UpdateAdvancedConfigurationHandlerInterface;
use izi\prestashop\Handler\Config\UpdateConsentsConfigurationHandlerInterface;
use izi\prestashop\Handler\Config\UpdateGeneralConfigurationHandlerInterface;
use izi\prestashop\Handler\Config\UpdateGuiConfigurationHandlerInterface;
use izi\prestashop\Handler\Config\UpdateShippingConfigurationHandlerInterface;
use izi\prestashop\Handler\GenerateDeepLinkHandlerInterface;
use izi\prestashop\Handler\GetBindingConfirmationHandlerInterface;
use izi\prestashop\Handler\GetClientDetailsHandlerInterface;
use izi\prestashop\Handler\GetOrderEventsHandlerInterface;
use izi\prestashop\Handler\UnbindBasketHandlerInterface;
use izi\prestashop\Handler\UpdateBasketHandlerInterface;
use izi\prestashop\Handler\UpdateOrderTrackingNumbersHandlerInterface;
use izi\prestashop\MerchantApi\Command\ConfirmBasketBindingCommand;
use izi\prestashop\MerchantApi\Command\DeleteBasketBindingCommand;
use izi\prestashop\MerchantApi\Command\GetBasketCommand;
use izi\prestashop\MerchantApi\Command\UpdateOrderCommand;
use izi\prestashop\MerchantApi\Handler\ConfirmBasketBindingHandlerInterface;
use izi\prestashop\MerchantApi\Handler\DeleteBasketBindingHandlerInterface;
use izi\prestashop\MerchantApi\Handler\GetBasketHandlerInterface;
use izi\prestashop\MerchantApi\Handler\UpdateOrderHandlerInterface;
use Psr\Container\ContainerInterface;
use izi\prestashop\DependencyInjection\ServiceSubscriberInterface;

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
            UpdateOrderTrackingNumbersCommand::class => UpdateOrderTrackingNumbersHandlerInterface::class,
            UpdateBasketCommand::class => UpdateBasketHandlerInterface::class,

            /* widget */
            GetClientDetailsCommand::class => GetClientDetailsHandlerInterface::class,
            BindBasketCommand::class => '?' . BindBasketHandlerInterface::class,
            GenerateDeepLinkCommand::class => '?' . GenerateDeepLinkHandlerInterface::class,
            UnbindBasketCommand::class => '?' . UnbindBasketHandlerInterface::class,
            GetBindingConfirmationCommand::class => '?' . GetBindingConfirmationHandlerInterface::class,
            GetOrderEventsCommand::class => '?' . GetOrderEventsHandlerInterface::class,

            /* merchant API */
            ConfirmBasketBindingCommand::class => '?' . ConfirmBasketBindingHandlerInterface::class,
            DeleteBasketBindingCommand::class => '?' . DeleteBasketBindingHandlerInterface::class,
            GetBasketCommand::class => '?' . GetBasketHandlerInterface::class,
            MerchantApi\Command\UpdateBasketCommand::class => '?' . MerchantApi\Handler\UpdateBasketHandlerInterface::class,
            UpdateOrderCommand::class => '?' . UpdateOrderHandlerInterface::class,

            /* configuration */
            UpdateGeneralConfigurationCommand::class => '?' . UpdateGeneralConfigurationHandlerInterface::class,
            UpdateConsentsConfigurationCommand::class => '?' . UpdateConsentsConfigurationHandlerInterface::class,
            UpdateGuiConfigurationCommand::class => '?' . UpdateGuiConfigurationHandlerInterface::class,
            UpdateShippingConfigurationCommand::class => '?' . UpdateShippingConfigurationHandlerInterface::class,
            UpdateAdvancedConfigurationCommand::class => '?' . UpdateAdvancedConfigurationHandlerInterface::class,
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function handle($command)
    {
        $class = get_class($command);
        $handler = $this->locator->get($class);

        return $handler($command);
    }
}
