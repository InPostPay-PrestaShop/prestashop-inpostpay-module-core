<?php

use InPost\Izi\Upgrade\ConfigUpdaterTrait;
use izi\prestashop\CacheClearer\SymfonyCacheClearer;
use izi\prestashop\Common\Basket\ConsentRequirementType;
use izi\prestashop\Common\BindingPlace;
use izi\prestashop\Configuration\DTO\Consent;
use izi\prestashop\Configuration\DTO\ConsentLink;
use izi\prestashop\Configuration\DTO\HtmlStyles;
use izi\prestashop\View\Widget\FrameStyle;
use izi\prestashop\View\Widget\Variant;
use izi\prestashop\View\Widget\WidgetConfiguration;

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/ConfigUpdaterTrait.php';

class InPostIziUpdater_1_5_0
{
    use ConfigUpdaterTrait;

    private const CART_WIDGET_CONFIG_MAP = [
        'INPOST_PAY_background_cart' => 'darkMode',
        'INPOST_PAY_variant_cart' => 'variant',
        'INPOST_PAY_frame_style_cart' => 'frameStyle',
        'INPOST_PAY_min_width_cart' => 'minWidthPx',
        'INPOST_PAY_max_width_cart' => 'maxWidthPx',
    ];

    private const PRODUCT_WIDGET_CONFIG_MAP = [
        'INPOST_PAY_alignment_details' => 'alignment',
        'INPOST_PAY_background_details' => 'darkMode',
        'INPOST_PAY_variant_details' => 'variant',
        'INPOST_PAY_frame_style_details' => 'frameStyle',
        'INPOST_PAY_min_width_details' => 'minWidthPx',
        'INPOST_PAY_max_width_details' => 'maxWidthPx',
    ];

    private const CART_STYLES_CONFIG_MAP = [
        'INPOST_PAY_margin_cart_up' => 'marginTop',
        'INPOST_PAY_margin_cart_left' => 'marginLeft',
        'INPOST_PAY_margin_cart_right' => 'marginRight',
        'INPOST_PAY_margin_cart_down' => 'marginBottom',
        'INPOST_PAY_alignment_cart' => 'alignment',
    ];

    private const PRODUCT_STYLES_CONFIG_MAP = [
        'INPOST_PAY_alignment_details' => 'alignment',
    ];

    /**
     * @var Db
     */
    private $db;

    public function __construct(Db $db)
    {
        $this->db = $db;
    }

    public function upgrade(): bool
    {
        SymfonyCacheClearer::getInstance()->clear();

        return $this->updateEnabledConfigValue()
            && $this->updateConsentStructure()
            && $this->updateWidgetConfigStructure();
    }

    private function updateEnabledConfigValue(): bool
    {
        return $this->db->execute(sprintf('
            UPDATE `%sconfiguration`
            SET `value` = (`value` = 2)
            WHERE `name` = "INPOST_PAY_show_izi"
        ', _DB_PREFIX_));
    }

    private function updateConsentStructure(): bool
    {
        $consentsByShopGroup = [];
        $configIds = [];

        $languageIds = Language::getLanguages(false, false, true);

        $requirementTypes = [
            'additional' => ConsentRequirementType::Optional(),
            'required' => ConsentRequirementType::RequiredAlways(),
            'required_once' => ConsentRequirementType::RequiredOnce(),
        ];

        foreach ($requirementTypes as $key => $requirementType) {
            $cmsIdsKey = sprintf('INPOST_PAY_terms_options_%s', $key);
            $textKey = sprintf('%s_text', $cmsIdsKey);

            $sql = (new DbQuery())
                ->select('c.*')
                ->from('configuration', 'c')
                ->where(sprintf('c.name IN ("%s", "%s")', $cmsIdsKey, $textKey));

            $data = $this->db->executeS($sql);

            if ([] === $data) {
                continue;
            }

            $dataByShopGroup = $this->groupConfigValuesByShop($data, $configIds);

            foreach ($dataByShopGroup as $shopGroupId => $dataByShop) {
                foreach ($dataByShop as $shopId => $data) {
                    $cmsPageIds = explode(',', $data[$cmsIdsKey]);
                    $text = trim($data[$textKey]);
                    if ('' === $text) {
                        continue;
                    }

                    $descriptions = [];
                    foreach ($languageIds as $languageId) {
                        $descriptions[$languageId] = $text;
                    }

                    $dateUpdated = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $data['date_upd']);

                    foreach ($cmsPageIds as $index => $cmsPageId) {
                        if (0 >= $cmsPageId = (int) $cmsPageId) {
                            continue;
                        }

                        $consentsByShopGroup[$shopGroupId][$shopId][] = new Consent(
                            new ConsentLink((string) $index, $cmsPageId),
                            $descriptions,
                            $requirementType,
                            [],
                            $dateUpdated
                        );
                    }
                }
            }
        }

        if (!$this->setJsonConfigValues('INPOST_PAY_CONSENTS', $consentsByShopGroup)) {
            return false;
        }

        if ([] === $configIds) {
            return true;
        }

        return $this->db->delete('configuration', 'id_configuration IN (' . implode(',', $configIds) . ')');
    }

    private function updateWidgetConfigStructure(): bool
    {
        return $this->updateCartWidgetConfigStructure()
            && $this->updateProductWidgetConfigStructure();
    }

    private function updateCartWidgetConfigStructure(): bool
    {
        $configs = $this->getWidgetConfigs(self::CART_WIDGET_CONFIG_MAP, BindingPlace::BasketSummary());
        $styles = $this->getWidgetStyles(self::CART_STYLES_CONFIG_MAP);

        return $this->setJsonConfigValues('INPOST_PAY_CART_WIDGET_CONFIG', $configs)
            && $this->setJsonConfigValues('INPOST_PAY_CART_HTML_STYLES', $styles)
            && $this->deleteConfigurationByKeys(array_keys(array_merge(self::CART_WIDGET_CONFIG_MAP, self::CART_STYLES_CONFIG_MAP)));
    }

    private function updateProductWidgetConfigStructure(): bool
    {
        $configs = $this->getWidgetConfigs(self::PRODUCT_WIDGET_CONFIG_MAP, BindingPlace::ProductCard());
        $styles = $this->getWidgetStyles(self::PRODUCT_STYLES_CONFIG_MAP);

        return $this->setJsonConfigValues('INPOST_PAY_PRODUCT_CARD_WIDGET_CONFIG', $configs)
            && $this->setJsonConfigValues('INPOST_PAY_PRODUCT_HTML_STYLES', $styles)
            && $this->deleteConfigurationByKeys(array_keys(self::PRODUCT_WIDGET_CONFIG_MAP));
    }

    private function getWidgetConfigs(array $map, BindingPlace $bindingPlace): array
    {
        if ([] === $data = $this->getConfigDataByKeys(array_keys($map))) {
            return [];
        }

        $dataByShopGroup = $this->groupConfigValuesByShop($data);

        $configs = [];

        foreach ($dataByShopGroup as $shopGroupId => $dataByShop) {
            foreach ($dataByShop as $shopId => $data) {
                $config = [];
                foreach ($map as $key => $value) {
                    $config[$value] = $data[$key] ?? null;
                }

                $maxWidth = $this->getWidgetWidth((int) $config['maxWidthPx']);

                $configs[$shopGroupId][$shopId] = (new WidgetConfiguration($bindingPlace))
                    ->setVariant(Variant::tryFrom($config['variant']) ?? Variant::Secondary())
                    ->setDarkMode((bool) $config['darkMode'])
                    ->setFrameStyle(FrameStyle::tryFrom($config['frameStyle']))
                    ->setMaxWidthPx($maxWidth);
            }
        }

        return $configs;
    }

    private function getWidgetStyles(array $map): array
    {
        if ([] === $data = $this->getConfigDataByKeys(array_keys($map))) {
            return [];
        }

        $dataByShopGroup = $this->groupConfigValuesByShop($data);

        $styles = [];

        foreach ($dataByShopGroup as $shopGroupId => $dataByShop) {
            foreach ($dataByShop as $shopId => $data) {
                $config = [];
                foreach ($map as $key => $value) {
                    $config[$value] = $data[$key] ?? null;
                }

                $justifyContent = HtmlStyles::getJustifyContentStyleByAlignment($config['alignment'] ?? null);

                $styles[$shopGroupId][$shopId] = (new HtmlStyles())
                    ->setMarginTop($config['marginTop'] ?? null)
                    ->setMarginLeft($config['marginLeft'] ?? null)
                    ->setMarginRight($config['marginRight'] ?? null)
                    ->setMarginBottom($config['marginBottom'] ?? null)
                    ->setJustifyContent($justifyContent);
            }
        }

        return $styles;
    }

    private function groupConfigValuesByShop(array $data, array &$configIds = []): array
    {
        $dataByShopGroup = [];

        foreach ($data as $row) {
            $dateUpdated = $dataByShopGroup[(int) $row['id_shop_group']][(int) $row['id_shop']]['date_upd'] ?? null;

            $dataByShopGroup[(int) $row['id_shop_group']][(int) $row['id_shop']][$row['name']] = $row['value'];
            if (null === $dateUpdated || $row['date_upd'] < $dateUpdated) {
                $dataByShopGroup[(int) $row['id_shop_group']][(int) $row['id_shop']]['date_upd'] = $row['date_upd'];
            }

            $configIds[] = $row['id_configuration'];
        }

        return $dataByShopGroup;
    }

    private function getWidgetWidth(int $width): ?int
    {
        return WidgetConfiguration::WIDTH_MIN_PX <= $width && WidgetConfiguration::WIDTH_MAX_PX >= $width ? $width : null;
    }
}

/**
 * @param InPostIzi $module
 *
 * @return bool
 */
function upgrade_module_1_5_0(Module $module)
{
    return (new InPostIziUpdater_1_5_0(Db::getInstance()))->upgrade();
}
