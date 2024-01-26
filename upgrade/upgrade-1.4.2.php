<?php

use izi\prestashop\Common\Basket\ConsentRequirementType;
use izi\prestashop\Configuration\DTO\Consent;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * @param \InPostIzi $module
 *
 * @return bool
 */
function upgrade_module_1_4_2(\Module $module)
{
    $db = \Db::getInstance();

    $consentsByShopGroup = [];
    $configIds = [];

    $languageIds = \Language::getLanguages(false, false, true);

    $requirementTypes = [
        'additional' => ConsentRequirementType::Optional(),
        'required' => ConsentRequirementType::RequiredAlways(),
        'required_once' => ConsentRequirementType::RequiredOnce(),
    ];

    foreach ($requirementTypes as $key => $requirementType) {
        $cmsIdsKey = sprintf('INPOST_PAY_terms_options_%s', $key);
        $textKey = sprintf('%s_text', $cmsIdsKey);

        $sql = (new \DbQuery())
            ->select('c.*')
            ->from('configuration', 'c')
            ->where(sprintf('c.name IN ("%s", "%s")', $cmsIdsKey, $textKey));

        $data = $db->executeS($sql);

        if ([] === $data) {
            continue;
        }

        $dataByShopGroup = [];
        foreach ($data as $row) {
            $dateUpdated = $dataByShopGroup[(int) $row['id_shop_group']][(int) $row['id_shop']]['date_upd'] ?? null;

            $dataByShopGroup[(int) $row['id_shop_group']][(int) $row['id_shop']][$row['name']] = $row['value'];
            if (null === $dateUpdated || $row['date_upd'] < $dateUpdated) {
                $dataByShopGroup[(int) $row['id_shop_group']][(int) $row['id_shop']]['date_upd'] = $row['date_upd'];
            }

            $configIds[] = $row['id_configuration'];
        }

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

                $dateUpdated = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $data['date_upd']);

                foreach ($cmsPageIds as $cmsPageId) {
                    if (0 >= $cmsPageId) {
                        continue;
                    }

                    $consentsByShopGroup[$shopGroupId][$shopId][] = new Consent(
                        null,
                        (int) $cmsPageId,
                        $descriptions,
                        $requirementType,
                        $dateUpdated
                    );
                }
            }
        }
    }

    foreach ($consentsByShopGroup as $shopGroupId => $consentsByShop) {
        foreach ($consentsByShop as $shopId => $consents) {
            $value = json_encode($consents);
            if (!\Configuration::updateValue('INPOST_PAY_CONSENTS', $value, false, $shopGroupId, $shopId)) {
                return false;
            }
        }
    }

    return $db->delete('configuration', 'id_configuration IN (' . implode(',', $configIds) . ')')
        && $db->execute(sprintf('
            UPDATE `%sconfiguration`
            SET `value` = (`value` = 2)
            WHERE `name` = "INPOST_PAY_show_izi"
        ', _DB_PREFIX_));
}
