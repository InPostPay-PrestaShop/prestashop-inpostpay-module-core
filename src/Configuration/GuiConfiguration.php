<?php

declare(strict_types=1);

namespace izi\prestashop\Configuration;

use izi\prestashop\Common\BindingPlace;
use izi\prestashop\Configuration\DTO\HtmlStyles;
use izi\prestashop\Configuration\DTO\Product\ProductRestrictions;
use izi\prestashop\Configuration\DTO\Product\ProductRestrictionsCache;
use izi\prestashop\Configuration\DTO\WidgetDisplayConfiguration;
use izi\prestashop\DependencyInjection\ServiceSubscriberInterface;
use izi\prestashop\Product\Restriction\RestrictedAction;
use izi\prestashop\Repository\Product\FeatureRestrictionsRepositoryInterface;
use izi\prestashop\Repository\ProductRestrictionsRepositoryInterface;
use izi\prestashop\Serializer\SafeDeserializerTrait;
use izi\prestashop\Validator\Product\NotFromRestrictedManufacturer;
use izi\prestashop\Validator\Product\NotInRestrictedCategory;
use izi\prestashop\Validator\Product\NotOfType;
use izi\prestashop\Validator\Product\NotWithRestrictedAttributes;
use izi\prestashop\Validator\Product\NotWithRestrictedFeatures;
use izi\prestashop\View\Widget\WidgetConfiguration;
use izi\prestashop\View\Widget\WidgetConfigurationInterface;
use PrestaShop\PrestaShop\Adapter\Shop\Context;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @implements PersistentConfigurationInterface<GuiConfigurationInterface>
 */
final class GuiConfiguration implements GuiConfigurationInterface, PersistentConfigurationInterface, ServiceSubscriberInterface, ProductRestrictionsConfigurationInterface
{
    use SafeDeserializerTrait;

    private const BASKET_SUMMARY_WIDGET_DISPLAY = 'INPOST_PAY_show_button_cart';
    private const BASKET_SUMMARY_WIDGET_CONFIG = 'INPOST_PAY_CART_WIDGET_CONFIG';
    private const BASKET_SUMMARY_HTML_STYLES = 'INPOST_PAY_CART_HTML_STYLES';

    private const PRODUCT_CARD_WIDGET_DISPLAY = 'INPOST_PAY_show_button_details';
    private const PRODUCT_CARD_WIDGET_CONFIG = 'INPOST_PAY_PRODUCT_CARD_WIDGET_CONFIG';
    private const PRODUCT_CARD_HTML_STYLES = 'INPOST_PAY_PRODUCT_HTML_STYLES';
    private const PRODUCT_PAGE_RESTRICTIONS = 'INPOST_PAY_PRODUCT_PAGE_RESTRICTIONS';
    private const PRODUCT_RESTRICTED_ACTION = 'INPOST_PAY_PRODUCT_RESTRICTED_ACTION';

    private const LOGIN_PAGE_WIDGET_DISPLAY = 'INPOST_PAY_SHOW_LOGIN_PAGE_WIDGET';
    private const LOGIN_PAGE_WIDGET_CONFIG = 'INPOST_PAY_LOGIN_PAGE_WIDGET_CONFIG';
    private const LOGIN_PAGE_HTML_STYLES = 'INPOST_PAY_LOGIN_PAGE_HTML_STYLES';

    private const REGISTERFORM_PAGE_WIDGET_DISPLAY = 'INPOST_PAY_SHOW_REGISTERFORM_PAGE_WIDGET';
    private const REGISTERFORM_PAGE_WIDGET_CONFIG = 'INPOST_PAY_REGISTERFORM_PAGE_WIDGET_CONFIG';
    private const REGISTERFORM_PAGE_HTML_STYLES = 'INPOST_PAY_REGISTERFORM_PAGE_HTML_STYLES';

    private const CHECKOUT_PAGE_WIDGET_DISPLAY = 'INPOST_PAY_SHOW_CHECKOUT_PAGE_WIDGET';
    private const CHECKOUT_PAGE_WIDGET_CONFIG = 'INPOST_PAY_CHECKOUT_PAGE_WIDGET_CONFIG';
    private const CHECKOUT_PAGE_HTML_STYLES = 'INPOST_PAY_CHECKOUT_PAGE_HTML_STYLES';

    private const MINICART_PAGE_WIDGET_DISPLAY = 'INPOST_PAY_SHOW_MINICART_PAGE_WIDGET';
    private const MINICART_PAGE_WIDGET_CONFIG = 'INPOST_PAY_MINICART_PAGE_WIDGET_CONFIG';
    private const MINICART_PAGE_HTML_STYLES = 'INPOST_PAY_MINICART_PAGE_HTML_STYLES';

    /**
     * @var ConfigurationInterface
     */
    private $configuration;

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var array<string, WidgetDisplayConfiguration>
     */
    private $loadedConfiguration = [];

    /**
     * @var array<int, ProductRestrictionsCache>
     */
    private $productRestrictions = [];

    /**
     * @var array<int, Constraint[]>
     */
    private $productValidationConstraints = [];

    /**
     * @var BindingPlace[]
     */
    private static $supportedBindingPlaces;

    public function __construct(ConfigurationInterface $configuration, SerializerInterface $serializer, ContainerInterface $container, ?LoggerInterface $logger = null)
    {
        $this->configuration = $configuration;
        $this->serializer = $serializer;
        $this->container = $container;
        $this->logger = $logger;
    }

    public static function getSubscribedServices(): array
    {
        return [
            ProductRestrictionsRepositoryInterface::class,
            'context' => Context::class,
            'validator' => '?' . ValidatorInterface::class,
        ];
    }

    public static function getSupportedBindingPlaces(): array
    {
        return self::$supportedBindingPlaces ?? self::$supportedBindingPlaces = [
            BindingPlace::BasketSummary(),
            BindingPlace::ProductCard(),
            BindingPlace::LoginPage(),
            BindingPlace::RegisterFormPage(),
            BindingPlace::CheckoutPage(),
            BindingPlace::MiniCartPage(),
            BindingPlace::OrderCreate(),
        ];
    }

    public static function getConfigurableBindingPlaces(): array
    {
        return array_slice(self::getSupportedBindingPlaces(), 0, -1); // all supported except "ORDER_CREATE"
    }

    /**
     * @return WidgetDisplayConfigurationInterface<WidgetConfiguration>
     */
    public function getDisplayConfiguration(BindingPlace $bindingPlace): WidgetDisplayConfigurationInterface
    {
        if (!$bindingPlace->canDisplayBindingWidget()) {
            throw new \LogicException(sprintf('Binding widget cannot be displayed in "%s".', $bindingPlace->value));
        }

        if (!in_array($bindingPlace, self::getSupportedBindingPlaces(), true)) {
            throw new \DomainException(sprintf('Unsupported binding place: "%s".', $bindingPlace->value));
        }

        if (BindingPlace::OrderCreate() === $bindingPlace) {
            $configuration = clone $this->getDisplayConfigurationByBindingPlace(BindingPlace::BasketSummary());
            $configuration->setWidgetConfiguration(
                $configuration
                    ->getWidgetConfiguration()
                    ->withBindingPlace(BindingPlace::OrderCreate())
            );

            return $configuration;
        }

        if (BindingPlace::ProductCard() !== $bindingPlace || !$this->container->has('validator')) {
            return clone $this->getDisplayConfigurationByBindingPlace($bindingPlace);
        }

        $restrictedAction = $this->getProductRestrictedAction();

        return new ProductPageDisplayConfiguration(
            clone $this->getDisplayConfigurationByBindingPlace($bindingPlace),
            $this->container->get('validator'),
            $restrictedAction->hidesWidget() ? $this->getProductValidationConstraints() : []
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getProductRestrictionConstraints(?int $shopId = null): array
    {
        return $this->getProductValidationConstraints($shopId);
    }

    public function getProductRestrictedAction(?int $shopId = null): RestrictedAction
    {
        $value = (int) $this->configuration->get(self::PRODUCT_RESTRICTED_ACTION, $shopId);

        return RestrictedAction::tryFrom($value) ?? RestrictedAction::HideWidget();
    }

    public function copy(): GuiConfigurationInterface
    {
        $displayConfigurations = [];

        foreach (self::getConfigurableBindingPlaces() as $bindingPlace) {
            $displayConfiguration = clone $this->getDisplayConfigurationByBindingPlace($bindingPlace);

            if (BindingPlace::ProductCard() === $bindingPlace) {
                $displayConfiguration = new DTO\ProductPageDisplayConfiguration(
                    $displayConfiguration,
                    $this->getProductRestrictions()
                );
            }

            $displayConfigurations[] = $displayConfiguration;
        }

        return new DTO\GuiConfiguration($displayConfigurations);
    }

    public function persist(GuiConfigurationInterface $configuration): void
    {
        $configurablePlaces = self::getConfigurableBindingPlaces();
        $productRestrictions = null;

        foreach ($configuration->getSupportedBindingPlaces() as $bindingPlace) {
            if (!in_array($bindingPlace, $configurablePlaces, true)) {
                continue;
            }

            $displayConfiguration = $configuration->getDisplayConfiguration($bindingPlace);

            if ($displayConfiguration instanceof ProductRestrictionsProviderInterface && BindingPlace::ProductCard() === $bindingPlace) {
                $productRestrictions = $displayConfiguration->getProductRestrictions() ?? new ProductRestrictions();
            }

            $this->setWidgetDisplayConfiguration($displayConfiguration);
        }

        if (null === $productRestrictions) {
            return;
        }

        $this->updateProductRestrictions($productRestrictions);
    }

    private function setWidgetDisplayConfiguration(WidgetDisplayConfigurationInterface $widgetDisplayConfig): void
    {
        $bindingPlace = $widgetDisplayConfig->getWidgetConfiguration()->getBindingPlace();

        $this->configuration->set($this->getDisplayWidgetConfigKey($bindingPlace), $widgetDisplayConfig->isDisplayed());
        $this->setHtmlStyles($widgetDisplayConfig->getHtmlStyles(), $bindingPlace);
        $this->setWidgetConfiguration($widgetDisplayConfig->getWidgetConfiguration(), $bindingPlace);
    }

    private function setHtmlStyles(iterable $styles, BindingPlace $bindingPlace): void
    {
        $value = $this->serializer->serialize($styles, 'json');
        $this->configuration->set($this->getHtmlStyleConfigKey($bindingPlace), $value);
    }

    private function setWidgetConfiguration(WidgetConfigurationInterface $config, BindingPlace $bindingPlace): void
    {
        $value = $this->serializer->serialize($config, 'json');
        $this->configuration->set($this->getConfigurationWidgetConfigKey($bindingPlace), $value);
    }

    private function getDisplayConfigurationByBindingPlace(BindingPlace $bindingPlace): WidgetDisplayConfiguration
    {
        $key = $bindingPlace->value;

        if (!isset($this->loadedConfiguration[$key])) {
            $this->loadedConfiguration[$key] = $this->loadDisplayConfiguration($bindingPlace);
        }

        return $this->loadedConfiguration[$key];
    }

    private function loadDisplayConfiguration(BindingPlace $bindingPlace): WidgetDisplayConfiguration
    {
        if (null === $configuration = $this->loadWidgetConfiguration($bindingPlace)) {
            return WidgetDisplayConfiguration::for($bindingPlace);
        }

        $displayed = (bool) $this->configuration->get($this->getDisplayWidgetConfigKey($bindingPlace));
        $htmlStyles = $this->loadHtmlStyles($bindingPlace);

        return new WidgetDisplayConfiguration($configuration, $displayed, $htmlStyles);
    }

    private function loadWidgetConfiguration(BindingPlace $bindingPlace): ?WidgetConfiguration
    {
        $value = $this->configuration->get($this->getConfigurationWidgetConfigKey($bindingPlace));

        if (null !== $value && $config = $this->deserialize($value, WidgetConfiguration::class)) {
            return $config;
        }

        return null;
    }

    private function loadHtmlStyles(BindingPlace $bindingPlace): ?HtmlStyles
    {
        $value = $this->configuration->get($this->getHtmlStyleConfigKey($bindingPlace));

        if (null !== $value && $styles = $this->deserialize($value, HtmlStyles::class)) {
            return $styles;
        }

        return null;
    }

    private function getHtmlStyleConfigKey(BindingPlace $bindingPlace): string
    {
        $constantName = $bindingPlace->value . '_HTML_STYLES';
        $classNamespace = self::class;

        return constant($classNamespace . '::' . $constantName);
    }

    private function getDisplayWidgetConfigKey(BindingPlace $bindingPlace): string
    {
        $constantName = $bindingPlace->value . '_WIDGET_DISPLAY';
        $classNamespace = self::class;

        return constant($classNamespace . '::' . $constantName);
    }

    private function getConfigurationWidgetConfigKey(BindingPlace $bindingPlace): string
    {
        $constantName = $bindingPlace->value . '_WIDGET_CONFIG';
        $classNamespace = self::class;

        return constant($classNamespace . '::' . $constantName);
    }

    private function setProductRestrictedAction(RestrictedAction $action, ?int $shopId = null): void
    {
        $this->configuration->set(self::PRODUCT_RESTRICTED_ACTION, $action->value, $shopId);
    }

    private function getProductRestrictions(): ProductRestrictions
    {
        $shopId = $this->getContextShopId();
        $productTypes = $this
            ->getCachedProductRestrictions($shopId)
            ->getProductTypes();

        return $this
            ->getProductRestrictionsRepository()
            ->getProductRestrictions($shopId)
            ->setProductTypes($productTypes)
            ->setRestrictedAction($this->getProductRestrictedAction($shopId));
    }

    private function updateProductRestrictions(ProductRestrictions $restrictions): void
    {
        $this->setProductRestrictedAction($restrictions->getRestrictedAction() ?? RestrictedAction::HideWidget());

        $repository = $this->getProductRestrictionsRepository();

        $repository->updateProductRestrictions($restrictions, $shopId = $this->getContextShopId());
        $this->cacheProductRestrictions(
            ProductRestrictionsCache::fromRestrictions($restrictions),
            $shopId
        );

        if (null !== $shopId) {
            return;
        }

        foreach ($this->getContext()->getContextListShopID() as $shopId) {
            $shopId = (int) $shopId;
            $cache = $this
                ->getCachedProductRestrictions($shopId)
                ->setHasCategoryRestrictions($repository->hasCategoryRestrictions($shopId))
                ->setHasManufacturerRestrictions($repository->hasManufacturerRestrictions($shopId))
                ->setHasAttributeGroupRestrictions($repository->hasAttributeGroupRestrictions($shopId));

            if (!$repository instanceof FeatureRestrictionsRepositoryInterface) {
                @trigger_error(sprintf('Not implementing "%s()" in "%s" is deprecated since version 2.2.0.', FeatureRestrictionsRepositoryInterface::class, get_class($repository)), E_USER_DEPRECATED);

                $cache->setHasFeatureRestrictions(false);
            } else {
                $cache->setHasFeatureRestrictions($repository->hasFeatureRestrictions($shopId));
            }

            $this->cacheProductRestrictions($cache, $shopId);
        }
    }

    private function getCachedProductRestrictions(?int $shopId): ProductRestrictionsCache
    {
        if (!isset($this->productRestrictions[(int) $shopId])) {
            $this->productRestrictions[(int) $shopId] = $this->loadCachedProductRestrictions($shopId);
        }

        return $this->productRestrictions[(int) $shopId];
    }

    private function loadCachedProductRestrictions(?int $shopId): ProductRestrictionsCache
    {
        $value = $this->configuration->get(self::PRODUCT_PAGE_RESTRICTIONS, $shopId);

        if (null !== $value && $restrictions = $this->deserialize($value, ProductRestrictionsCache::class)) {
            return $restrictions;
        }

        return new ProductRestrictionsCache();
    }

    private function cacheProductRestrictions(ProductRestrictionsCache $restrictions, ?int $shopId): void
    {
        $value = $this->serializer->serialize($restrictions, 'json');
        $this->configuration->set(self::PRODUCT_PAGE_RESTRICTIONS, $value, $shopId);

        $this->productRestrictions[(int) $shopId] = $restrictions;
    }

    private function getContextShopId(): ?int
    {
        if (null === $shopId = $this->getContext()->getContextShopID()) {
            return null;
        }

        return (int) $shopId;
    }

    private function getProductRestrictionsRepository(): ProductRestrictionsRepositoryInterface
    {
        return $this->container->get(ProductRestrictionsRepositoryInterface::class);
    }

    private function getContext(): Context
    {
        return $this->container->get('context');
    }

    private function getProductValidationConstraints(?int $shopId = null): array
    {
        $shopId = $shopId ?? $this->getContextShopId();
        $key = (int) $shopId;

        return $this->productValidationConstraints[$key] ?? $this->productValidationConstraints[$key] = iterator_to_array($this->generateProductValidationConstraints($shopId));
    }

    private function generateProductValidationConstraints(?int $shopId): \Generator
    {
        $restrictions = $this->getCachedProductRestrictions($shopId);

        if ([] !== $types = $restrictions->getProductTypes()) {
            yield new NotOfType(['types' => $types]);
        }

        if ($restrictions->hasCategoryRestrictions()) {
            yield new NotInRestrictedCategory(['shopId' => $shopId]);
        }

        if ($restrictions->hasManufacturerRestrictions()) {
            yield new NotFromRestrictedManufacturer(['shopId' => $shopId]);
        }

        if ($restrictions->hasAttributeGroupRestrictions()) {
            yield new NotWithRestrictedAttributes(['shopId' => $shopId]);
        }

        if ($restrictions->hasFeatureRestrictions()) {
            yield new NotWithRestrictedFeatures(['shopId' => $shopId]);
        }
    }
}
