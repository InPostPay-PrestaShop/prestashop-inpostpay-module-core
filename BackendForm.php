<?php

use izi\prestashop\Common\Basket\ConsentRequirementType;
use izi\prestashop\Common\BindingPlace;
use izi\prestashop\Common\Delivery\DeliveryType;
use izi\prestashop\Common\Delivery\ServiceCode;
use izi\prestashop\Configuration\DTO\Consent;
use izi\prestashop\Configuration\DTO\HtmlStyles;
use izi\prestashop\Configuration\DTO\Shipping\CarrierMapping;
use izi\prestashop\Configuration\DTO\Shipping\ServiceOptions;
use izi\prestashop\Configuration\DTO\Shipping\ShippingOptions;
use izi\prestashop\Configuration\DTO\Shipping\TimeOfWeek;
use izi\prestashop\Configuration\DTO\Shipping\TimeOfWeekRange;
use izi\prestashop\Configuration\DTO\Shipping\WeekDay;
use izi\prestashop\Configuration\DTO\ShippingConfiguration;
use izi\prestashop\Configuration\ShippingConfigurationInterface;
use izi\prestashop\Environment\EnvironmentType;
use izi\prestashop\Environment\UatEnvironment;
use izi\prestashop\Translation\LegacyTranslator;
use izi\prestashop\Translation\ServiceNameTranslator;
use izi\prestashop\View\Widget\Alignment;
use izi\prestashop\View\Widget\Configuration as WidgetConfiguration;
use izi\prestashop\View\Widget\FrameStyle;
use izi\prestashop\View\Widget\Variant;
use izi\prestashop\Hook\Front\DisplayPaymentReturn;
use izi\prestashop\Hook\Front\DisplayOrderConfirmation;
use izi\prestashop\Hook\Front\DisplayIziThankYou;

/**
 * @mixin \Module
 */
trait BackendForm
{
    protected function apiFormFields()
    {
        $fields = [
            [
                'type' => 'select',
                'label' => $this->l('Środowisko'),
                'name' => 'INPOST_PAY_environment',
                'options' => [
                    'query' => $this->EnvironmentOptions(),
                    'id' => 'id_option',
                    'name' => 'name',
                ],
            ],
            [
                'type' => 'select',
                'label' => $this->l('Pokazuj widget'),
                'name' => 'INPOST_PAY_show_izi',
                'options' => [
                    'query' => $this->ShowOptions(),
                    'id' => 'id_option',
                    'name' => 'name',
                ],
            ],
            [
                'type' => 'text',
                'label' => $this->l('Client ID'),
                'name' => 'INPOST_PAY_client_id',
                'class' => 'fixed-width-xl',
            ],
            [
                'type' => 'text',
                'label' => $this->l('Client Secret'),
                'name' => 'INPOST_PAY_client_secret',
                'class' => 'fixed-width-xl',
            ],
            [
                'type' => 'text',
                'label' => $this->l('POS ID'),
                'name' => 'INPOST_PAY_pos_id',
                'class' => 'fixed-width-xl',
            ],
            [
                'type' => 'switch',
                'label' => $this->l('Włącz płatności zgodne z podpisaną umową z Aion'),
                'name' => 'INPOST_PAY_payment_aion',
                'is_bool' => true,
                'values' => [
                    [
                        'id' => 'active_on',
                        'value' => 1,
                        'label' => $this->l('Tak'),
                    ],
                    [
                        'id' => 'active_off',
                        'value' => 0,
                        'label' => $this->l('Nie'),
                    ],
                ],
            ],
            [
                'type' => 'switch',
                'label' => $this->l('Włącz płatność przy odbiorze zgodnie z podpisaną umową z InPost'),
                'name' => 'INPOST_PAY_payment_inpost',
                'is_bool' => true,
                'values' => [
                    [
                        'id' => 'active_on',
                        'value' => 1,
                        'label' => $this->l('Tak'),
                    ],
                    [
                        'id' => 'active_off',
                        'value' => 0,
                        'label' => $this->l('Nie'),
                    ],
                ],
            ],
            [
                'type' => 'select',
                'class' => 'fixed-width-xxl',
                'label' => $this->l('Hook do wyświetlania widgetu na stronie potwierdzenia zamówienia'),
                'name' => 'INPOST_PAY_THANK_YOU_DISPLAY',
                'options' => [
                    'query' => $this->getThankYouDisplayOptions(),
                    'id' => 'id_option',
                    'name' => 'name',
                ],
                'desc' => sprintf($this->l('Jeśli wybierzesz hook `%s` musisz go ręcznie zaimplementować w pliku templates/checkout/order-confirmation.tpl `{hook h="%s" order=$order}`.', 'backendform'), DisplayIziThankYou::getHookName(), DisplayIziThankYou::getHookName()),
            ],
            [
                'type' => 'select',
                'class' => 'fixed-width-xxl',
                'label' => $this->l('Początkowy status zamówienia utworzonego przez InPost Pay'),
                'name' => 'INPOST_PAY_INITIAL_OS_ID',
                'options' => [
                    'query' => $this->StatusOptions(),
                    'id' => 'id_option',
                    'name' => 'name',
                ],
            ],
            [
                'type' => 'select',
                'class' => 'fixed-width-xxl',
                'label' => $this->l('Statusy dla zamówienia opłaconego przez InPost Pay'),
                'name' => 'INPOST_PAY_authorized_payment',
                'options' => [
                    'query' => $this->StatusOptions(),
                    'id' => 'id_option',
                    'name' => 'name',
                ],
            ],
        ];

        foreach (\OrderState::getOrderStates($this->context->language->id) as $status) {
            $fields[] = [
                'type' => 'text',
                'label' => $status['name'],
                'name' => sprintf('os_description_%d', $status['id_order_state']),
                'lang' => true,
                'hint' => $this->l('If left empty the order state name will be presented.', 'backendform'),
                'col' => 4,
                'id_order_state' => $status['id_order_state'],
            ];
        }

        $fields[] = [
            'type' => 'text',
            'label' => $this->l('Maksymalna liczba produktów sugerowanych'),
            'name' => 'INPOST_PAY_related_count',
            'class' => 'text-right fixed-width-xl',
            'hint' => $this->l('If left empty the number of related products will not be limited.', 'backendform'),
        ];

        return $fields;
    }

    protected function consentsFormFields()
    {
        return [
            [
                'type' => 'select',
                'class' => 'fixed-width-xxl',
                'label' => $this->l('Zgody Wymagane'),
                'name' => 'INPOST_PAY_terms_options_required',
                'multiple' => true,
                'options' => [
                    'query' => $this->TermsOptions(),
                    'id' => 'id_option',
                    'name' => 'name',
                ],
                'consent_cms_ids' => ConsentRequirementType::RequiredAlways(),
            ],
            [
                'type' => 'text',
                'lang' => true,
                'label' => $this->l('Zgody Wymagane Tekst'),
                'name' => 'INPOST_PAY_terms_options_required_text',
                'consent_descriptions' => ConsentRequirementType::RequiredAlways(),
            ],
            [
                'type' => 'select',
                'class' => 'fixed-width-xxl',
                'label' => $this->l('Zgody Wymagane Raz'),
                'name' => 'INPOST_PAY_terms_options_required_once',
                'multiple' => true,
                'options' => [
                    'query' => $this->TermsOptions(),
                    'id' => 'id_option',
                    'name' => 'name',
                ],
                'consent_cms_ids' => ConsentRequirementType::RequiredOnce(),
            ],
            [
                'type' => 'text',
                'label' => $this->l('Zgody Wymagane Raz Tekst'),
                'lang' => true,
                'name' => 'INPOST_PAY_terms_options_required_once_text',
                'consent_descriptions' => ConsentRequirementType::RequiredOnce(),
            ],
            [
                'type' => 'select',
                'class' => 'fixed-width-xxl',
                'label' => $this->l('Zgody Dodatkowe'),
                'name' => 'INPOST_PAY_terms_options_additional',
                'multiple' => true,
                'options' => [
                    'query' => $this->TermsOptions(),
                    'id' => 'id_option',
                    'name' => 'name',
                ],
                'consent_cms_ids' => ConsentRequirementType::Optional(),
            ],
            [
                'type' => 'text',
                'lang' => true,
                'label' => $this->l('Zgody Dodatkowe Tekst'),
                'name' => 'INPOST_PAY_terms_options_additional_text',
                'consent_descriptions' => ConsentRequirementType::Optional(),
            ],
        ];
    }

    protected function paymentFormFields()
    {
        return array_merge(
            $this->getShippingConfigFields(DeliveryType::Courier(), 'Kurier'),
            $this->getShippingConfigFields(DeliveryType::Apm(), 'Paczkomat')
        );
    }

    private function getShippingConfigFields(DeliveryType $deliveryType, $typeLabel)
    {
        return array_merge(
            $this->getCarrierMappingFields($deliveryType, $typeLabel),
            ...array_map(function (ServiceCode $serviceCode) use ($deliveryType, $typeLabel) {
                return $this->getServiceOptionsFields($deliveryType, $serviceCode, $typeLabel);
            }, $deliveryType->getAvailableServiceCodes())
        );
    }

    private function getCarrierMappingFields(DeliveryType $deliveryType, $typeLabel)
    {
        $fields = [];
        $carrierOptions = $this->DeliveryOptions();

        foreach (ServiceCode::getAvailableCombinations($deliveryType) as $serviceCodes) {
            $name = sprintf('carrier_mapping_%s_%s', $deliveryType->value, $this->getCarrierMappingKey($serviceCodes));

            $fields[] = [
                'type' => 'select',
                'class' => 'fixed-width-xxl',
                'label' => $this->getCarrierMappingLabel($typeLabel, $serviceCodes),
                'name' => $name,
                'options' => [
                    'query' => $carrierOptions,
                    'id' => 'id_option',
                    'name' => 'name',
                    'default' => [
                        'label' => '--',
                        'value' => null,
                    ],
                ],
                'carrier_mapping' => [$deliveryType, $serviceCodes],
            ];
        }

        return $fields;
    }
    
    private function getServiceOptionsFields(DeliveryType $deliveryType, ServiceCode $serviceCode, $typeLabel)
    {
        $serviceName = $this->getServiceName($serviceCode);

        $fields = [
            [
                'type' => 'text',
                'label' => sprintf('%s %s netto', $typeLabel, $serviceName),
                'name' => sprintf('service_cost_%s_%s', $deliveryType->value, $serviceCode->value),
                'class' => 'text-right fixed-width-xl',
                'suffix' => 'PLN',
                'service_option' => ['cost', $deliveryType, $serviceCode],
            ],
        ];

        if (!$serviceCode->isAvailabilityTimeDependent()) {
            return $fields;
        }

        return array_merge($fields, [
            [
                'type' => 'select',
                'label' => sprintf('%s %s dostępne od dnia', $typeLabel, $serviceName),
                'name' => sprintf('service_start_day_%s_%s', $deliveryType->value, $serviceCode->value),
                'options' => [
                    'query' => $this->DayOptions(),
                    'id' => 'id_option',
                    'name' => 'name',
                ],
                'service_option' => ['start_day', $deliveryType, $serviceCode],
            ],
            [
                'type' => 'select',
                'label' => sprintf('%s %s dostępne od godziny', $typeLabel, $serviceName),
                'hint' => 'test',
                'name' => sprintf('service_start_time_%s_%s', $deliveryType->value, $serviceCode->value),
                'options' => [
                    'query' => $this->TimeOptions(),
                    'id' => 'id_option',
                    'name' => 'name',
                ],
                'service_option' => ['start_time', $deliveryType, $serviceCode],
            ],
            [
                'type' => 'select',
                'label' => sprintf('%s %s dostępne do dnia', $typeLabel, $serviceName),
                'name' => sprintf('service_end_day_%s_%s', $deliveryType->value, $serviceCode->value),
                'options' => [
                    'query' => $this->DayOptions(),
                    'id' => 'id_option',
                    'name' => 'name',
                ],
                'service_option' => ['end_day', $deliveryType, $serviceCode],
            ],
            [
                'type' => 'select',
                'label' => sprintf('%s %s dostępne do godziny', $typeLabel, $serviceName),
                'name' => sprintf('service_end_time_%s_%s', $deliveryType->value, $serviceCode->value),
                'options' => [
                    'query' => $this->TimeOptions(),
                    'id' => 'id_option',
                    'name' => 'name',
                ],
                'service_option' => ['end_time', $deliveryType, $serviceCode],
            ],
        ]);
    }

    protected function guiFormFields()
    {
        $fields = [];
        $translations = [
            'cart' => $this->l('koszyk'),
            'list' => $this->l('lista'),
            'details' => $this->l('karta produktu'),
        ];

        $translationsDirection = [
            'top' => $this->l('Góra'),
            'bottom' => $this->l('Dół'),
            'left' => $this->l('Lewo'),
            'right' => $this->l('Prawo'),
        ];
        foreach (['cart', 'details'] as $place) {
            $fields[] = [
                'type' => 'switch',
                'label' => sprintf($this->l('Wyświetlaj %s'), $translations[$place]),
                'name' => 'INPOST_PAY_show_button_' . $place,
                'is_bool' => true,
                'values' => [
                    [
                        'id' => 'active_on',
                        'value' => 1,
                        'label' => $this->l('Tak'),
                    ],
                    [
                        'id' => 'active_off',
                        'value' => 0,
                        'label' => $this->l('Nie'),
                    ],
                ],
            ];
            $fields[] = [
                'type' => 'select',
                'label' => $this->l('Wyrównanie'),
                'name' => 'INPOST_PAY_alignment_' . $place,
                'options' => [
                    'query' => $this->AlignmentOptions(),
                    'id' => 'id_option',
                    'name' => 'name',
                ],
                'widget_config' => $place,
                'widget_attribute' => 'alignment',
            ];
            $fields[] = [
                'type' => 'select',
                'label' => $this->l('Tło'),
                'name' => 'INPOST_PAY_background_' . $place,
                'options' => [
                    'query' => $this->BackgroundOptions(),
                    'id' => 'id_option',
                    'name' => 'name',
                ],
                'widget_config' => $place,
                'widget_attribute' => 'darkMode',
            ];
            $fields[] = [
                'type' => 'select',
                'label' => $this->l('Wariant'),
                'name' => 'INPOST_PAY_variant_' . $place,
                'options' => [
                    'query' => $this->VariantOptions(),
                    'id' => 'id_option',
                    'name' => 'name',
                ],
                'widget_config' => $place,
                'widget_attribute' => 'variant',
            ];
            $fields[] = [
                'type' => 'select',
                'label' => $this->l('Styl rogów'),
                'name' => 'INPOST_PAY_frame_style_' . $place,
                'options' => [
                    'query' => $this->getFrameStyleChoices(),
                    'id' => 'id_option',
                    'name' => 'name',
                ],
                'widget_config' => $place,
                'widget_attribute' => 'frameStyle',
            ];
            $fields[] = [
                'type' => 'text',
                'label' => $this->l('Minimalna szerokość'),
                'name' => 'INPOST_PAY_min_width_' . $place,
                'hint' => $this->l('Valid values range: 220 - 600 px', 'backendform'),
                'class' => 'text-right fixed-width-xl',
                'suffix' => 'px',
                'widget_config' => $place,
                'widget_attribute' => 'minWidthPx',
            ];
            $fields[] = [
                'type' => 'text',
                'label' => $this->l('Maksymalna szerokość'),
                'name' => 'INPOST_PAY_max_width_' . $place,
                'hint' => $this->l('Valid values range: 220 - 600 px', 'backendform'),
                'class' => 'text-right fixed-width-xl',
                'suffix' => 'px',
                'widget_config' => $place,
                'widget_attribute' => 'maxWidthPx',
            ];

            foreach (['top', 'bottom', 'left', 'right'] as $direction) {
                $fields[] = [
                    'type' => 'text',
                    'label' => sprintf($this->l('Margines %s'), $translationsDirection[$direction]),
                    'name' => 'INPOST_PAY_margin_' . $place . '_' . $direction,
                    'suffix' => 'px',
                    'class' => 'text-right fixed-width-xl',
                    'widget_styles' => $place,
                    'widget_style' => 'margin' . ucfirst($direction),
                ];
            }
        }

        return $fields;
    }

    protected function formFields()
    {
        return array_merge(
            $this->apiFormFields(),
            $this->consentsFormFields(),
            $this->paymentFormFields(),
            $this->guiFormFields()
        );
    }

    private function doGetContent()
    {
        $output = '';
        if (Tools::isSubmit('submit' . $this->name)) {
            $languageIds = \Language::getLanguages(false, false, true);
            $descriptionMaps = [];
            $consentData = [];
            $widgetConfig = [];
            $widgetStyles = [];
            $carrierMappings = [];
            $serviceOptions = [];

            foreach ($this->formFields() as $field) {
                if (!empty($field['lang'])) {
                    $configValue = [];

                    foreach ($languageIds as $languageId) {
                        $configValue[$languageId] = trim(\Tools::getValue(sprintf('%s_%d', $field['name'], $languageId)));
                    }
                } else {
                    $configValue = Tools::getValue($field['name']);
                }

                if (isset($field['carrier_mapping'])) {
                    list($deliveryType, $serviceCodes) = $field['carrier_mapping'];
                    $referenceId = empty($configValue) ? null : (int) $configValue;
                    $key = $this->getCarrierMappingKey($serviceCodes);
                    $carrierMappings[$deliveryType->value][$key] = new CarrierMapping($referenceId, ...$serviceCodes);

                    continue;
                }

                if (isset($field['service_option'])) {
                    list($option, $deliveryType, $serviceCode) = $field['service_option'];
                    $serviceOptions[$deliveryType->value][$serviceCode->value][$option] = $configValue;

                    continue;
                }

                if (isset($field['id_order_state'])) {
                    $osId = (int) $field['id_order_state'];
                    foreach ($configValue as $languageId => $value) {
                        $descriptionMaps[$languageId][$osId] = $value ?: null;
                    }

                    continue;
                }

                if ($field['name'] === 'INPOST_PAY_client_secret' && $configValue === '*****') {
                    continue;
                }

                if (isset($field['widget_config'])) {
                    $widgetConfig[$field['widget_config']][$field['widget_attribute']] = $configValue;

                    continue;
                }

                if (isset($field['widget_styles'])) {
                    $widgetStyles[$field['widget_styles']][$field['widget_style']] = $configValue === '' ? null : (int) $configValue;

                    continue;
                }

                if (isset($field['consent_descriptions'])) {
                    $consentData[$field['consent_descriptions']->value]['descriptions'] = $configValue;

                    continue;
                }

                if (isset($field['consent_cms_ids'])) {
                    $consentData[$field['consent_cms_ids']->value]['cms_ids'] = is_array($configValue) ? $configValue : [];

                    continue;
                }

                if (isset($field['multiple']) && $field['multiple']) {
                    if (is_array($configValue)) {
                        Configuration::updateValue($field['name'], implode(',', $configValue));
                    } else {
                        Configuration::updateValue($field['name'], '');
                    }
                } else {
                    Configuration::updateValue($field['name'], $configValue);
                }
            }

            \Configuration::updateValue('INPOST_PAY_OS_DESCRIPTION_MAP', array_map(static function (array $map) {
                return json_encode(array_filter($map));
            }, $descriptionMaps));

            $this->updateConsents($consentData);
            $this->updateWidgetConfig($widgetConfig);
            $this->updateWidgetStyles($widgetStyles);
            $this->updateShippingConfiguration($carrierMappings, $serviceOptions);

            (new \izi\prestashop\Configuration\Adapter\Configuration())->removeMatching('INPOST_PAY_CACHE_*');

            $output = $this->displayConfirmation($this->l('Settings updated', 'backendform'));
        }

        return $output . $this->displayForm();
    }

    public function displayForm()
    {
        $form = [
            'form' => [
                'legend' => [
                    'title' => $this->l('Ustawienia Inpost Pay'),
                ],
                'input' => $this->formFields(),
                'submit' => [
                    'title' => $this->l('Save', 'backendform'),
                    'icon' => 'process-icon-save',
                ],
            ],
        ];

        $helper = new HelperForm();

        $helper->table = $this->table;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex . '&' . http_build_query(['configure' => $this->name]);
        $helper->submit_action = 'submit' . $this->name;
        $helper->languages = $this->context->controller->getLanguages();
        $helper->default_form_language = $this->context->language->id;

        foreach ($helper->languages as $language) {
            $value = \Configuration::get('INPOST_PAY_OS_DESCRIPTION_MAP', $language['id_lang']);
            if (!$value) {
                $osDescriptions[$language['id_lang']] = [];
            } else {
                $decoded = json_decode($value, true);
                $osDescriptions[$language['id_lang']] = null !== $decoded ? $decoded : [];
            }
        }

        $consents = json_decode(\Configuration::get('INPOST_PAY_CONSENTS'), true);
        $consents = null !== $consents ? $consents : [];
        $consentsByType = [];
        foreach ($consents as $consent) {
            $consentsByType[$consent['requirementType']]['descriptions'] = $consent['descriptions'];
            $consentsByType[$consent['requirementType']]['cms_ids'][] = $consent['cmsPageId'];
        }

        $widgetConfigs = [
            'cart' => json_decode(\Configuration::get('INPOST_PAY_CART_WIDGET_CONFIG'), true),
            'details' => json_decode(\Configuration::get('INPOST_PAY_PRODUCT_CARD_WIDGET_CONFIG'), true),
        ];

        $widgetStyles = [
            'cart' => json_decode(\Configuration::get('INPOST_PAY_CART_HTML_STYLES'), true),
            'details' => json_decode(\Configuration::get('INPOST_PAY_PRODUCT_HTML_STYLES'), true),
        ];

        $shippingConfig = $this->get(ShippingConfigurationInterface::class);

        foreach ($this->formFields() as $field) {
            if (isset($field['carrier_mapping'])) {
                list($deliveryType, $serviceCodes) = $field['carrier_mapping'];
                $helper->tpl_vars['fields_value'][$field['name']] = $shippingConfig->getShippingOptions($deliveryType)->getCarrierMapping(...$serviceCodes)->getReferenceId();
            } elseif (isset($field['service_option'])) {
                list($option, $deliveryType, $serviceCode) = $field['service_option'];
                $options = $shippingConfig->getShippingOptions($deliveryType)->getServiceOptions($serviceCode);
                $helper->tpl_vars['fields_value'][$field['name']] = null === $options ? null : $this->getOptionValue($options, $option);
            } elseif (isset($field['id_order_state'])) {
                foreach ($helper->languages as $language) {
                    $helper->tpl_vars['fields_value'][$field['name']][$language['id_lang']] = \Tools::getValue(
                        sprintf('%s_%d', $field['name'], $language['id_lang']),
                        isset($osDescriptions[$language['id_lang']][$field['id_order_state']]) ? $osDescriptions[$language['id_lang']][$field['id_order_state']] : null
                    );
                }
            } elseif (isset($field['consent_cms_ids'])) {
                $cmsIds = isset($consentsByType[$field['consent_cms_ids']->value]['cms_ids']) ? $consentsByType[$field['consent_cms_ids']->value]['cms_ids'] : [];
                $helper->tpl_vars['fields_value'][$field['name'] . '[]'] = $cmsIds;
            } elseif (isset($field['consent_descriptions'])) {
                $descriptions = isset($consentsByType[$field['consent_descriptions']->value]['descriptions']) ? $consentsByType[$field['consent_descriptions']->value]['descriptions'] : null;
                $helper->tpl_vars['fields_value'][$field['name']] = $descriptions;
            } elseif (isset($field['widget_config'])) {
                $helper->tpl_vars['fields_value'][$field['name']] = isset($widgetConfigs[$field['widget_config']][$field['widget_attribute']]) ? $widgetConfigs[$field['widget_config']][$field['widget_attribute']] : null;
            } elseif (isset($field['widget_styles'])) {
                $helper->tpl_vars['fields_value'][$field['name']] = isset($widgetStyles[$field['widget_styles']][$field['widget_style']]) ? $widgetStyles[$field['widget_styles']][$field['widget_style']] : null;
            } elseif ($field['name'] === 'INPOST_PAY_client_secret') {
                $helper->tpl_vars['fields_value'][$field['name']] = \Configuration::get($field['name']) ? '*****' : '';
            } elseif (isset($field['multiple']) && $field['multiple']) {
                $helper->tpl_vars['fields_value'][$field['name'] . '[]'] = explode(',', \Configuration::get($field['name']));
            } else {
                $helper->tpl_vars['fields_value'][$field['name']] = \Configuration::get($field['name']);
            }
        }

        return $helper->generateForm([$form]);
    }

    private function getOptionValue(ServiceOptions $options, $name)
    {
        if ('cost' === $name) {
            return $options->getAdditionalCost();
        }

        if (null === $timeRange = $options->getAvailabilityRange()) {
            return null;
        }

        switch ($name) {
            case 'start_day':
                return $timeRange->getStart()->getWeekDay()->value;
            case 'start_time':
                return $timeRange->getStart()->getTime()->format('H:i');
            case 'end_day':
                return $timeRange->getEnd()->getWeekDay()->value;
            case 'end_time':
                return $timeRange->getEnd()->getTime()->format('H:i');
            default:
                return null;
        }
    }

    protected function EnvironmentOptions()
    {
        $options = [];
        if (class_exists(UatEnvironment::class)) {
            $options[] = [
                'id_option' => EnvironmentType::Uat()->value,
                'name' => 'Deweloperskie',
            ];
        }
        $options[] = [
            'id_option' => EnvironmentType::Sandbox()->value,
            'name' => 'Sandbox',
        ];
        $options[] = [
            'id_option' => EnvironmentType::Production()->value,
            'name' => 'Produkcyjne',
        ];

        return $options;
    }

    protected function ShowOptions()
    {
        return [
            [
                'id_option' => 1,
                'name' => 'Testerom',
            ],
            [
                'id_option' => 2,
                'name' => 'Wszystkim',
            ],
        ];
    }

    protected function TermsOptions()
    {
        $shopId = \Shop::CONTEXT_SHOP === \Shop::getContext()
            ? $this->context->shop->id
            : null;

        $cmsPages = CMS::getCMSPages($this->context->language->id, null, true, $shopId);

        $options = [];

        foreach ($cmsPages as $page) {
            $options[$page['id_cms']] = [
                'id_option' => $page['id_cms'],
                'name' => $page['meta_title'],
            ];
        }

        return $options;
    }

    protected function StatusOptions()
    {
        $options = [];

        foreach (\OrderState::getOrderStates($this->context->language->id) as $status) {
            $options[] = [
                'id_option' => $status['id_order_state'],
                'name' => $status['name'],
            ];
        }

        return $options;
    }

    protected function getThankYouDisplayOptions()
    {
        return [
            [
                'id_option' => DisplayPaymentReturn::getHookName(),
                'name' => DisplayPaymentReturn::getHookName(),
            ],
            [
                'id_option' => DisplayOrderConfirmation::getHookName(),
                'name' => DisplayOrderConfirmation::getHookName(),
            ],
            [
                'id_option' => DisplayIziThankYou::getHookName(),
                'name' => DisplayIziThankYou::getHookName(),
            ],
        ];
    }

    protected function DeliveryOptions()
    {
        $options = [];

        $carrierList = Db::getInstance()->executeS('select id_reference, name from `' . _DB_PREFIX_ . 'carrier` WHERE deleted = 0');
        foreach ($carrierList as $carrier) {
            $options[] = [
                'id_option' => $carrier['id_reference'],
                'name' => $carrier['name'],
            ];
        }

        return $options;
    }

    protected function DayOptions()
    {
        $options = [];
        foreach (['Poniedziałek', 'Wtorek', 'Środa', 'Czwartek', 'Piątek', 'Sobota', 'Niedziela'] as $key => $name) {
            $options[] = [
                'id_option' => $key + 1,
                'name' => $name,
            ];
        }

        return $options;
    }

    protected function TimeOptions()
    {
        $options = [];
        foreach (range(0, 23) as $hour) {
            $time = \DateTime::createFromFormat('G', $hour)->format('H:i');

            $options[] = [
                'id_option' => $time,
                'name' => $time,
            ];
        }

        return $options;
    }

    protected function AlignmentOptions()
    {
        return [
            [
                'id_option' => Alignment::Left()->value,
                'name' => 'Do lewej',
            ],
            [
                'id_option' => Alignment::Center()->value,
                'name' => 'Do środka',
            ],
            [
                'id_option' => Alignment::Right()->value,
                'name' => 'Do prawej',
            ],
        ];
    }

    protected function BackgroundOptions()
    {
        return [
            [
                'id_option' => 0,
                'name' => 'Jasne',
            ],
            [
                'id_option' => 1,
                'name' => 'Ciemne',
            ],
        ];
    }

    protected function VariantOptions()
    {
        return [
            [
                'id_option' => Variant::Secondary()->value,
                'name' => 'Czarny',
            ],
            [
                'id_option' => Variant::Primary()->value,
                'name' => 'Żółty',
            ],
        ];
    }

    protected function getFrameStyleChoices()
    {
        return [
            [
                'id_option' => null,
                'name' => 'Prostokątne',
            ],
            [
                'id_option' => FrameStyle::Rounded()->value,
                'name' => 'Małe zaokrąglenie',
            ],
            [
                'id_option' => FrameStyle::Round()->value,
                'name' => 'Duże zaokrąglenie',
            ],
        ];
    }

    private function updateConsents(array $consentData)
    {
        $consents = [];
        $now = new \DateTimeImmutable();

        foreach ($consentData as $requirementType => $data) {
            $requirementType = ConsentRequirementType::from($requirementType);

            foreach ($data['cms_ids'] as $cmsPageId) {
                $consents[] = new Consent(
                    null,
                    (int) $cmsPageId,
                    $data['descriptions'],
                    $requirementType,
                    $now
                );
            }
        }

        \Configuration::updateValue('INPOST_PAY_CONSENTS', json_encode($consents));
    }

    private function updateWidgetConfig(array $configs)
    {
        foreach ($configs as $place => $config) {
            if ('cart' === $place) {
                $bindingPlace = BindingPlace::BasketSummary();
                $basket = true;
                $configKey = 'INPOST_PAY_CART_WIDGET_CONFIG';
            } else {
                $bindingPlace = BindingPlace::ProductCard();
                $basket = false;
                $configKey = 'INPOST_PAY_PRODUCT_CARD_WIDGET_CONFIG';
            }

            $minWidth = $this->getWidgetWidth((int) $config['minWidthPx']);
            $maxWidth = $this->getWidgetWidth((int) $config['maxWidthPx']);

            $variant = Variant::tryFrom($config['variant']);

            $configuration = (new WidgetConfiguration($bindingPlace, $basket))
                ->setVariant($variant !== null ? $variant : Variant::Secondary())
                ->setDarkMode((bool) $config['darkMode'])
                ->setAlignment(Alignment::tryFrom($config['alignment']))
                ->setFrameStyle(FrameStyle::tryFrom($config['frameStyle']))
                ->setMinWidthPx($minWidth)
                ->setMaxWidthPx($maxWidth);

            \Configuration::updateValue($configKey, json_encode($configuration));
        }
    }

    private function updateWidgetStyles(array $styles)
    {
        foreach ($styles as $place => $values) {
            if ('cart' === $place) {
                $configKey = 'INPOST_PAY_CART_HTML_STYLES';
            } else {
                $configKey = 'INPOST_PAY_PRODUCT_HTML_STYLES';
            }

            $htmlStyles = (new HtmlStyles())
                ->setMarginTop($values['marginTop'])
                ->setMarginLeft($values['marginLeft'])
                ->setMarginRight($values['marginRight'])
                ->setMarginBottom($values['marginBottom']);

            \Configuration::updateValue($configKey, json_encode($htmlStyles));
        }
    }

    private function updateShippingConfiguration(array $carrierMappings, array $serviceOptions)
    {
        $configuration = new ShippingConfiguration(
            $this->createShippingOptions($carrierMappings['APM'], $serviceOptions['APM']),
            $this->createShippingOptions($carrierMappings['COURIER'], $serviceOptions['COURIER'])
        );

        $this->get(ShippingConfigurationInterface::class)->persist($configuration);
    }

    private function createShippingOptions(array $carrierMappings, array $serviceOptions)
    {
        foreach ($serviceOptions as $code => $options) {
            $serviceCode = ServiceCode::from($code);
            $availabilityRange = $serviceCode->isAvailabilityTimeDependent() ? $this->createTimeRange($options) : null;

            $serviceOptions[$code] = new ServiceOptions(
                $serviceCode,
                $options['cost'],
                $availabilityRange
            );
        }

        return new ShippingOptions($carrierMappings, $serviceOptions);
    }

    private function createTimeRange(array $options)
    {
        $start = new TimeOfWeek(
            WeekDay::from((int) $options['start_day']),
            \DateTimeImmutable::createFromFormat('H:i', $options['start_time'])
        );

        $end = new TimeOfWeek(
            WeekDay::from((int) $options['end_day']),
            \DateTimeImmutable::createFromFormat('H:i', $options['end_time'])
        );

        return new TimeOfWeekRange($start, $end);
    }

    /**
     * @param int $width
     *
     * @return int|null
     */
    private function getWidgetWidth($width)
    {
        return WidgetConfiguration::WIDTH_MIN_PX <= $width && WidgetConfiguration::WIDTH_MAX_PX >= $width ? $width : null;
    }

    private function getCarrierMappingKey(array $serviceCodes)
    {
        if ([] === $serviceCodes) {
            return 'default';
        }

        return implode(':', array_map(static function (ServiceCode $serviceCode) {
            return $serviceCode->value;
        }, $serviceCodes));
    }

    private function getCarrierMappingLabel($typeLabel, array $serviceCodes)
    {
        $label = sprintf('Mapowanie przewoźnika: %s', $typeLabel);

        if ([] === $serviceCodes) {
            return $label;
        }

        return sprintf('%s (%s)', $label, implode(' + ', array_map(function (ServiceCode $serviceCode) {
            return $this->getServiceName($serviceCode);
        }, $serviceCodes)));
    }

    private function getServiceName(ServiceCode $serviceCode)
    {
        static $translator;

        if (!isset($translator)) {
            $translator = new ServiceNameTranslator(new LegacyTranslator($this->name));
        }

        return $translator->getName($serviceCode);
    }
}
