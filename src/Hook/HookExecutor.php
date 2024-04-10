<?php

declare(strict_types=1);

namespace izi\prestashop\Hook;

use PrestaShop\PrestaShop\Core\Module\WidgetInterface;
use Psr\Container\ContainerInterface;
use izi\prestashop\DependencyInjection\ServiceSubscriberInterface;

final class HookExecutor implements HookExecutorInterface, ServiceSubscriberInterface
{
    /**
     * @var ContainerInterface
     */
    private $locator;

    /**
     * @var WidgetInterface|null
     */
    private $widget;

    public function __construct(ContainerInterface $locator, WidgetInterface $widget = null)
    {
        $this->locator = $locator;
        $this->widget = $widget;
    }

    public static function getSubscribedServices(): array
    {
        return [
            Admin\DisplayAdminOrderSide::HOOK_NAME => '?' . Admin\DisplayAdminOrderSide::class,
            Common\ActionCartDeleteBefore::HOOK_NAME => Common\ActionCartDeleteBefore::class,
            Common\ActionCartSave::HOOK_NAME => Common\ActionCartSave::class,
            Common\ActionValidateOrder::HOOK_NAME => Common\ActionValidateOrder::class,
            Common\ActionShipmentAddAfter::HOOK_NAME => Common\ActionShipmentAddAfter::class,
            Common\ActionShipmentUpdateBefore::HOOK_NAME => Common\ActionShipmentUpdateBefore::class,
            Common\ActionShipmentUpdateAfter::HOOK_NAME => Common\ActionShipmentUpdateAfter::class,
            Front\ActionCartControllerAjaxUpdateResponse::HOOK_NAME => '?' . Front\ActionCartControllerAjaxUpdateResponse::class,
            Front\ActionFrontControllerSetMedia::HOOK_NAME => '?' . Front\ActionFrontControllerSetMedia::class,
            Front\DisplayOrderConfirmation::HOOK_NAME => '?' . Front\DisplayOrderConfirmation::class,
            Front\DisplayPaymentReturn::HOOK_NAME => '?' . Front\DisplayPaymentReturn::class,
            Front\DisplayIziThankYou::HOOK_NAME => '?' . Front\DisplayIziThankYou::class,
            Front\DisplayProductAdditionalInfo::HOOK_NAME => '?' . Front\DisplayProductAdditionalInfo::class,
            Front\DisplayProductActions::HOOK_NAME => '?' . Front\DisplayProductActions::class,
            Front\DisplayExpressCheckout::HOOK_NAME => '?' . Front\DisplayExpressCheckout::class,
            Front\DisplayCustomerLoginFormAfter::HOOK_NAME => '?' . Front\DisplayCustomerLoginFormAfter::class,
            Front\DisplayCustomerAccountFormTop::HOOK_NAME => '?' . Front\DisplayCustomerAccountFormTop::class,
            Front\DisplayCheckoutSummaryTop::HOOK_NAME => '?' . Front\DisplayCheckoutSummaryTop::class,
            Front\DisplayIziCartPreviewButton::HOOK_NAME => '?' . Front\DisplayIziCartPreviewButton::class,
            Front\DisplayIziCheckoutButton::HOOK_NAME => '?' . Front\DisplayIziCheckoutButton::class,
        ];
    }

    public function execute(string $hookName, array $parameters)
    {
        if (null !== $hook = $this->getHook($hookName)) {
            return $hook->execute($parameters);
        }

        if (null !== $this->widget && \Hook::isDisplayHookName($hookName)) {
            return $this->widget->renderWidget($hookName, $parameters);
        }

        throw new \DomainException(sprintf('Hook "%s" is either not implemented or not available in the current context.', $hookName));
    }

    /**
     * @return string[]
     */
    public static function getHooksToInstall(string $psVersion): array
    {
        $hookNames = [];

        foreach (self::getSubscribedServices() as $hookName => $serviceName) {
            $class = '?' === $serviceName[0] ? \Tools::substr($serviceName, 1) : $serviceName;

            if (is_subclass_of($class, AliasedHookInterface::class)) {
                foreach (self::getAliases($class, $psVersion) as $alias) {
                    $hookNames[] = $alias;
                }
            } elseif (
                !is_subclass_of($class, PrestaShopVersionAwareHookInterface::class)
                || $class::getVersionRange()->contains($psVersion)
            ) {
                $hookNames[] = $hookName;
            }
        }

        return $hookNames;
    }

    private function getHook(string $name): ?HookInterface
    {
        return $this->locator->has($name) ? $this->locator->get($name) : null;
    }

    /**
     * @param class-string<AliasedHookInterface> $class
     *
     * @return string[]
     */
    private static function getAliases(string $class, string $psVersion): array
    {
        $aliases = [];

        foreach ($class::getAliases() as $alias => $versionRange) {
            if (null === $versionRange || $versionRange->contains($psVersion)) {
                $aliases[] = $alias;
            }
        }

        return $aliases;
    }
}
