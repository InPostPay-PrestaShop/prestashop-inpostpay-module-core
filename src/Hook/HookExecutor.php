<?php

declare(strict_types=1);

namespace izi\prestashop\Hook;

use izi\prestashop\DependencyInjection\ServiceSubscriberInterface;
use izi\prestashop\Handler\Config\UpdateGeneralConfigurationHandler;
use izi\prestashop\Hook\Exception\HookNotFoundException;
use izi\prestashop\Hook\Exception\HookNotImplementedException;
use PrestaShop\PrestaShop\Core\Module\WidgetInterface;
use Psr\Container\ContainerInterface;

final class HookExecutor implements HookExecutorInterface, ServiceSubscriberInterface
{
    /**
     * @internal
     */
    public const HOT_PRODUCT_HOOKS = [
        Common\Product\ActionProductDeleteBefore::HOOK_NAME,
        Common\Product\ActionProductDeleteAfter::HOOK_NAME,
        Common\Product\ActionProductUpdateAfter::HOOK_NAME,
        Common\Product\ActionCombinationDeleteBefore::HOOK_NAME,
        Common\Product\ActionCombinationDeleteAfter::HOOK_NAME,
        Common\Product\ActionCombinationUpdateAfter::HOOK_NAME,
        Common\Product\ActionImageAddAfter::HOOK_NAME,
        Common\Product\ActionImageDeleteAfter::HOOK_NAME,
        Common\Product\ActionSpecificPriceAddAfter::HOOK_NAME,
        Common\Product\ActionSpecificPriceUpdateAfter::HOOK_NAME,
        Common\Product\ActionSpecificPriceDeleteAfter::HOOK_NAME,
        Common\Product\ActionUpdateQuantity::HOOK_NAME,
    ];

    /**
     * Not installed by default, registration is handled by {@see UpdateGeneralConfigurationHandler}
     */
    private const OPTIONAL_HOOKS = [
        Front\DisplayProductAdditionalInfo::HOOK_NAME,
        Front\DisplayIziCheckoutButton::HOOK_NAME,
        Front\DisplayIziThankYou::HOOK_NAME,
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

    /**
     * @todo: must stay for now, used to list hooks to install
     */
    public static function getSubscribedServices(): array
    {
        return [
            Admin\ActionAdminControllerSetMedia::HOOK_NAME => '?' . Admin\ActionAdminControllerSetMedia::class,
            Admin\ActionAdminCartRuleSaveAfter::HOOK_NAME => '?' . Admin\ActionAdminCartRuleSaveAfter::class,
            Admin\DisplayBackOfficeHeader::HOOK_NAME => '?' . Admin\DisplayBackOfficeHeader::class,
            Admin\DisplayAdminOrderLeft::HOOK_NAME => '?' . Admin\DisplayAdminOrderLeft::class,
            Admin\DisplayAdminOrderSide::HOOK_NAME => '?' . Admin\DisplayAdminOrderSide::class,
            Admin\ActionAdminInPostConfirmedShipmentsControllerAfter::HOOK_NAME => '?' . Admin\ActionAdminInPostConfirmedShipmentsControllerAfter::class,
            Admin\ActionAdminInPostConfirmedShipmentsControllerBefore::HOOK_NAME => '?' . Admin\ActionAdminInPostConfirmedShipmentsControllerBefore::class,

            // admin products form
            Admin\Product\ActionProductFormBuilderModifier::HOOK_NAME => '?' . Admin\Product\ActionProductFormBuilderModifier::class,
            Admin\Product\ActionAfterUpdateProductFormHandler::HOOK_NAME => '?' . Admin\Product\ActionAfterUpdateProductFormHandler::class,
            Legacy\Admin\Product\DisplayAdminProductsOptionsStepBottom::HOOK_NAME => '?' . Legacy\Admin\Product\DisplayAdminProductsOptionsStepBottom::class,
            Legacy\Admin\Product\ActionAdminProductsSaveAfter::HOOK_NAME => '?' . Legacy\Admin\Product\ActionAdminProductsSaveAfter::class,

            Common\ActionCartDeleteBefore::HOOK_NAME => Common\ActionCartDeleteBefore::class,
            Common\ActionCartUpdateAfter::HOOK_NAME => Common\ActionCartUpdateAfter::class,
            Common\ActionValidateOrder::HOOK_NAME => Common\ActionValidateOrder::class,
            Common\ActionOrderStatusPostUpdate::HOOK_NAME => Common\ActionOrderStatusPostUpdate::class,
            Common\ActionShipmentAddAfter::HOOK_NAME => Common\ActionShipmentAddAfter::class,
            Common\ActionShipmentUpdateBefore::HOOK_NAME => Common\ActionShipmentUpdateBefore::class,
            Common\ActionShipmentUpdateAfter::HOOK_NAME => Common\ActionShipmentUpdateAfter::class,
            Common\ActionObjectOrderUpdateBefore::HOOK_NAME => Common\ActionObjectOrderUpdateBefore::class,
            Common\ActionObjectOrderUpdateAfter::HOOK_NAME => Common\ActionObjectOrderUpdateAfter::class,
            Common\ActionEmailSendBefore::HOOK_NAME => Common\ActionEmailSendBefore::class,

            // products
            Common\Product\ActionProductDeleteBefore::HOOK_NAME => Common\Product\ActionProductDeleteBefore::class,
            Common\Product\ActionProductDeleteAfter::HOOK_NAME => Common\Product\ActionProductDeleteAfter::class,
            Common\Product\ActionProductUpdateAfter::HOOK_NAME => Common\Product\ActionProductUpdateAfter::class,
            Common\Product\ActionCombinationDeleteBefore::HOOK_NAME => Common\Product\ActionCombinationDeleteBefore::class,
            Common\Product\ActionCombinationDeleteAfter::HOOK_NAME => Common\Product\ActionCombinationDeleteAfter::class,
            Common\Product\ActionCombinationUpdateAfter::HOOK_NAME => Common\Product\ActionCombinationUpdateAfter::class,
            Common\Product\ActionImageAddAfter::HOOK_NAME => Common\Product\ActionImageAddAfter::class,
            Common\Product\ActionImageDeleteAfter::HOOK_NAME => Common\Product\ActionImageDeleteAfter::class,
            Common\Product\ActionSpecificPriceAddAfter::HOOK_NAME => Common\Product\ActionSpecificPriceAddAfter::class,
            Common\Product\ActionSpecificPriceUpdateAfter::HOOK_NAME => Common\Product\ActionSpecificPriceUpdateAfter::class,
            Common\Product\ActionSpecificPriceDeleteAfter::HOOK_NAME => Common\Product\ActionSpecificPriceDeleteAfter::class,
            Common\Product\ActionUpdateQuantity::HOOK_NAME => Common\Product\ActionUpdateQuantity::class,

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
            Front\DisplayHeader::HOOK_NAME => '?' . Front\DisplayHeader::class,

            Webservice\AddWebserviceResources::HOOK_NAME => '?' . Webservice\AddWebserviceResources::class,
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

        throw HookNotImplementedException::create($hookName);
    }

    /**
     * @param array{enable_hot_products?: bool} $options
     *
     * @return string[]
     */
    public static function getHooksToInstall(string $psVersion, array $options = []): array
    {
        $hookNames = [];

        foreach (self::getSubscribedServices() as $hookName => $serviceName) {
            if (\in_array($hookName, self::OPTIONAL_HOOKS, true)) {
                continue;
            }

            if (empty($options['enable_hot_products']) && \in_array($hookName, self::HOT_PRODUCT_HOOKS, true)) {
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

        if (!$this->locator->has($canonicalName)) {
            throw HookNotFoundException::create($name);
        }

        return $this->locator->get($canonicalName);
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
