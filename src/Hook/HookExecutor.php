<?php

declare(strict_types=1);

namespace izi\prestashop\Hook;

use izi\prestashop\DependencyInjection\ServiceSubscriberInterface;
use PrestaShop\PrestaShop\Core\Module\WidgetInterface;
use Psr\Container\ContainerInterface;

final class HookExecutor implements HookExecutorInterface, ServiceSubscriberInterface
{
    private const DEPRECATED_HOOKS = [
        Common\ActionCartSave::HOOK_NAME,
    ];

    /**
     * @var ContainerInterface
     */
    private $locator;

    /**
     * @var WidgetInterface|null
     */
    private $widget;

    public function __construct(ContainerInterface $locator, ?WidgetInterface $widget = null)
    {
        $this->locator = $locator;
        $this->widget = $widget;
    }

    public static function getSubscribedServices(): array
    {
        return [
            Admin\DisplayAdminOrderLeft::HOOK_NAME => '?' . Admin\DisplayAdminOrderLeft::class,
            Admin\DisplayAdminOrderSide::HOOK_NAME => '?' . Admin\DisplayAdminOrderSide::class,

            Common\ActionCartDeleteBefore::HOOK_NAME => Common\ActionCartDeleteBefore::class,
            Common\ActionCartUpdateAfter::HOOK_NAME => Common\ActionCartUpdateAfter::class,
            Common\ActionValidateOrder::HOOK_NAME => Common\ActionValidateOrder::class,
            Common\ActionOrderStatusPostUpdate::HOOK_NAME => Common\ActionOrderStatusPostUpdate::class,
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

            // no longer used
            Common\ActionCartSave::HOOK_NAME => '?' . Common\ActionCartSave::class,
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
            if (in_array($hookName, self::DEPRECATED_HOOKS, true)) {
                continue;
            }

            $class = '?' === $serviceName[0] ? substr($serviceName, 1) : $serviceName;

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
        if ($this->locator->has($name)) {
            return $this->locator->get($name);
        }

        static $canonicalNames;

        if (!isset($canonicalNames)) {
            $canonicalNames = [];

            foreach (self::getSubscribedServices() as $canonicalName => $ignored) {
                $normalizedName = strtolower($canonicalName);
                $canonicalNames[$normalizedName] = $canonicalName;
            }
        }

        $normalizedName = strtolower($name);

        if (!isset($canonicalNames[$normalizedName])) {
            return null;
        }

        $canonicalName = $canonicalNames[$normalizedName];

        return $this->locator->has($canonicalName) ? $this->locator->get($canonicalName) : null;
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
