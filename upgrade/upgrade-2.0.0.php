<?php

use InPost\Izi\Upgrade\AssetsRemoverTrait;
use InPost\Izi\Upgrade\ConfigUpdaterTrait;
use izi\prestashop\CacheClearer\SymfonyCacheClearer;
use izi\prestashop\Common\BindingPlace;
use izi\prestashop\Configuration\Adapter\Configuration;
use izi\prestashop\Configuration\DTO\HtmlStyles;
use izi\prestashop\Configuration\GuiConfiguration;
use izi\prestashop\Database\Connection;
use izi\prestashop\Installer\Database\Version_2_0_0;
use izi\prestashop\Installer\DatabaseInstaller;

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/AssetsRemoverTrait.php';
require_once __DIR__ . '/ConfigUpdaterTrait.php';

class InPostIziUpdater_2_0_0
{
    use AssetsRemoverTrait;
    use ConfigUpdaterTrait;

    private const STALE_ASSETS = [
        'js/prestashopizi.109989890fd3720148dc.js',
        'css/product.6f69cd7d93f20866f321.css',
    ];

    private const CLASSES_TO_REMOVE = [
        izi\prestashop\Command\BindBasketCommand::class,
        izi\prestashop\Handler\BindBasketHandlerInterface::class,
        izi\prestashop\Handler\BindBasketHandler::class,
        izi\prestashop\Handler\Result\BasketBindingResult::class,
        izi\prestashop\Command\GenerateDeepLinkCommand::class,
        izi\prestashop\Handler\GenerateDeepLinkHandlerInterface::class,
        izi\prestashop\Handler\GenerateDeepLinkHandler::class,
        izi\prestashop\Handler\Result\DeepLink::class,
        izi\prestashop\Command\GetBindingConfirmationCommand::class,
        izi\prestashop\Handler\GetBindingConfirmationHandlerInterface::class,
        izi\prestashop\Handler\GetBindingConfirmationHandler::class,
        izi\prestashop\Handler\Result\BindingConfirmationStream::class,
        izi\prestashop\Command\GetClientDetailsCommand::class,
        izi\prestashop\Handler\GetClientDetailsHandlerInterface::class,
        izi\prestashop\Handler\GetClientDetailsHandler::class,
        izi\prestashop\Command\GetOrderEventsCommand::class,
        izi\prestashop\Handler\GetOrderEventsHandlerInterface::class,
        izi\prestashop\Handler\GetOrderEventsHandler::class,
        izi\prestashop\Handler\Result\OrderEvent::class,
        izi\prestashop\Handler\Result\OrderEventStream::class,
        izi\prestashop\Hook\Common\ActionCartSave::class,
        izi\prestashop\BasketApp\Basket\Request\BindingMethod::class,
        izi\prestashop\BasketApp\Basket\Request\BindingRequest::class,
        izi\prestashop\BasketApp\Basket\Request\Browser::class,
        izi\prestashop\BasketApp\Basket\Response\QrCode::class,
        izi\prestashop\BasketApp\Basket\Response\UpsertBasketResponse::class,
        izi\prestashop\BasketApp\Browser\BrowserApiClientInterface::class,
        izi\prestashop\BasketApp\Exception\BrowserNotFoundException::class,
        izi\prestashop\Hook\WidgetRenderer::class,
        izi\prestashop\Hook\WidgetConfigurationResolver::class,
        izi\prestashop\View\Widget\Alignment::class,
        izi\prestashop\View\Widget\Language::class,
        izi\prestashop\View\Widget\Configuration::class,
        izi\prestashop\Form\Type\Widget\WidgetAlignmentChoiceType::class,
        izi\prestashop\CartSession::class,
        izi\prestashop\Environment\UatEnvironment::class,
        izi\prestashop\Twig\Loader\TemplateNameMappingLoaderDecoratorTrait::class,
    ];

    private const FILES_TO_REMOVE = [
        'lib/',
        'classes/',
        'controllers/front/cart.php',
        'views/templates/hook/buttonWidget.tpl',
        'views/templates/hook/productButtonWidget.tpl',
        'views/templates/hook/widget.tpl',
        'views/templates/hook/backend.tpl',
        'views/templates/hook/admin_order_left.tpl',
    ];

    /**
     * @var DatabaseInstaller
     */
    private $installer;

    public function __construct(Module $module, DatabaseInstaller $installer, Db $db)
    {
        $this->module = $module;
        $this->installer = $installer;
        $this->db = $db;
    }

    public function upgrade(): bool
    {
        SymfonyCacheClearer::getInstance()->clear();
        $this->installer->install($this->module);

        return $this->updateGuiConfiguration()
            && $this->removeStaleAssets(self::STALE_ASSETS)
            && $this->removeClasses(self::CLASSES_TO_REMOVE)
            && $this->removeFiles(self::FILES_TO_REMOVE);
    }

    private function updateGuiConfiguration(): bool
    {
        $result = true;

        foreach ($this->getConfigurableBindingPlaces() as $bindingPlace) {
            $result &= $this->updateStylesConfig($bindingPlace);
        }

        return (bool) $result;
    }

    /**
     * Method may not exist if the previous version of the file was already included (e.g., during recompilation of the container)
     * before unpacking a new version of the module.
     */
    private function getConfigurableBindingPlaces(): array
    {
        if (method_exists(GuiConfiguration::class, 'getConfigurableBindingPlaces')) {
            return GuiConfiguration::getConfigurableBindingPlaces();
        }

        return [
            BindingPlace::BasketSummary(),
            BindingPlace::ProductCard(),
            BindingPlace::LoginPage(),
            BindingPlace::RegisterFormPage(),
            BindingPlace::CheckoutPage(),
            BindingPlace::MiniCartPage(),
            BindingPlace::OrderCreate(),
        ];
    }

    private function updateStylesConfig(BindingPlace $bindingPlace): bool
    {
        $data = $this->getConfigDataByKeys([
            $widgetConfigKey = self::getWidgetConfigKey($bindingPlace),
            $stylesConfigKey = self::getHtmlStylesConfigKey($bindingPlace),
        ]);

        if ([] === $data) {
            return true;
        }

        $dataByShopGroup = $this->groupConfigValuesByShop($data);

        $newWidgetConfigs = [];
        $newStyles = [];

        foreach ($dataByShopGroup as $shopGroupId => $dataByShop) {
            foreach ($dataByShop as $shopId => $data) {
                if (null === $widgetConfig = $data[$widgetConfigKey] ?? null) {
                    continue;
                }

                $widgetConfig = json_decode($widgetConfig, true);

                if (!is_array($widgetConfig) || !isset($widgetConfig['alignment'])) {
                    continue;
                }

                if (isset($data[$stylesConfigKey])) {
                    $stylesConfig = json_decode($data[$stylesConfigKey], true) ?? [];
                } else {
                    $stylesConfig = [];
                }

                $stylesConfig['justifyContent'] = HtmlStyles::getJustifyContentStyleByAlignment($widgetConfig['alignment']);
                unset($widgetConfig['alignment'], $widgetConfig['basket'], $widgetConfig['minWidthPx']);

                $newStyles[$shopGroupId][$shopId] = $stylesConfig;
                $newWidgetConfigs[$shopGroupId][$shopId] = $widgetConfig;
            }
        }

        return $this->setJsonConfigValues($widgetConfigKey, $newWidgetConfigs)
            && $this->setJsonConfigValues($stylesConfigKey, $newStyles);
    }

    private static function getWidgetConfigKey(BindingPlace $bindingPlace): string
    {
        if (BindingPlace::BasketSummary() === $bindingPlace) {
            return 'INPOST_PAY_CART_WIDGET_CONFIG';
        }

        return 'INPOST_PAY_' . $bindingPlace->value . '_WIDGET_CONFIG';
    }

    private static function getHtmlStylesConfigKey(BindingPlace $bindingPlace): string
    {
        if (BindingPlace::BasketSummary() === $bindingPlace) {
            return 'INPOST_PAY_CART_HTML_STYLES';
        }

        if (BindingPlace::ProductCard() === $bindingPlace) {
            return 'INPOST_PAY_PRODUCT_HTML_STYLES';
        }

        return 'INPOST_PAY_' . $bindingPlace->value . '_HTML_STYLES';
    }
}

/**
 * @param InPostIzi $module
 */
function upgrade_module_2_0_0(Module $module): bool
{
    if (Tools::version_compare(_PS_VERSION_, '1.7.1')) {
        try {
            $module->uninstall();
        } finally {
            return false;
        }
    }

    $db = Db::getInstance();
    $dbInstaller = new DatabaseInstaller([
        new Version_2_0_0(new Connection($db)),
    ], new Configuration($db));

    return (new InPostIziUpdater_2_0_0($module, $dbInstaller, $db))->upgrade();
}
