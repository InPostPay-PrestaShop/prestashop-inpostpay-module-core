<?php

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
                'size' => 20,
            ],
            [
                'type' => 'text',
                'label' => $this->l('Client Secret'),
                'name' => 'INPOST_PAY_client_secret',
                'size' => 20,
            ],
            [
                'type' => 'text',
                'label' => $this->l('POS ID'),
                'name' => 'INPOST_PAY_pos_id',
                'size' => 20,
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
                'label' => $this->l('Statusy dla zamówienia opłaconego przez InPost Pay'),
                'name' => 'INPOST_PAY_authorized_payment',
                'options' => [
                    'query' => $this->StatusOptions(),
                    'id' => 'id_option',
                    'name' => 'name',
                ],
            ],
        ];

        $states = new OrderState();
        foreach ($states->getOrderStates((int) Configuration::get('PS_LANG_DEFAULT')) as $status) {
            $fieldName = 'INPOST_PAY_status_translation_' . $status['id_order_state'];
            if (!\Configuration::get($fieldName)) {
                \Configuration::updateValue($fieldName, $status['name']);
            }

            $fields[] = [
                'type' => 'text',
                'label' => $status['name'],
                'name' => $fieldName,
            ];
        }

        $fields[] = [
            'type' => 'text',
            'label' => $this->l('Maksymalna liczba produktów sugerowanych'),
            'name' => 'INPOST_PAY_related_count',
        ];

        return $fields;
    }

    protected function consentsFormFields()
    {
        $fields = [
            [
                'type' => 'select',
                'label' => $this->l('Zgody Wymagane'),
                'name' => 'INPOST_PAY_terms_options_required',
                'multiple' => true,
                'options' => [
                    'query' => $this->TermsOptions(),
                    'id' => 'id_option',
                    'name' => 'name',
                ],
            ],
            [
                'type' => 'text',
                'label' => $this->l('Zgody Wymagane Tekst'),
                'name' => 'INPOST_PAY_terms_options_required_text',
            ],
            [
                'type' => 'select',
                'label' => $this->l('Zgody Wymagane Raz'),
                'name' => 'INPOST_PAY_terms_options_required_once',
                'multiple' => true,
                'options' => [
                    'query' => $this->TermsOptions(),
                    'id' => 'id_option',
                    'name' => 'name',
                ],
            ],
            [
                'type' => 'text',
                'label' => $this->l('Zgody Wymagane Raz Tekst'),
                'name' => 'INPOST_PAY_terms_options_required_once_text',
            ],
            [
                'type' => 'select',
                'label' => $this->l('Zgody Dodatkowe'),
                'name' => 'INPOST_PAY_terms_options_additional',
                'multiple' => true,
                'options' => [
                    'query' => $this->TermsOptions(),
                    'id' => 'id_option',
                    'name' => 'name',
                ],
            ],
            [
                'type' => 'text',
                'label' => $this->l('Zgody Dodatkowe Tekst'),
                'name' => 'INPOST_PAY_terms_options_additional_text',
            ],
        ];

        return $fields;
    }

    protected function paymentFormFields()
    {
        $fields = [
            [
                'type' => 'select',
                'label' => $this->l('Kurier'),
                'name' => 'INPOST_PAY_payment_courier',
                'options' => [
                    'query' => $this->DeliveryOptions(),
                    'id' => 'id_option',
                    'name' => 'name',
                ],
            ],
            [
                'type' => 'text',
                'label' => $this->l('Kurier paczka w weekend netto'),
                'name' => 'INPOST_PAY_payment_courier_pww',
                'size' => 20,
            ],
            [
                'type' => 'select',
                'label' => $this->l('Kurier paczka w weekend dostępne od dnia'),
                'name' => 'INPOST_PAY_payment_courier_pww_from_day',
                'options' => [
                    'query' => $this->DayOptions(),
                    'id' => 'id_option',
                    'name' => 'name',
                ],
            ],
            [
                'type' => 'select',
                'label' => $this->l('Kurier paczka w weekend dostępne od godziny'),
                'name' => 'INPOST_PAY_payment_courier_pww_from_time',
                'options' => [
                    'query' => $this->TimeOptions(),
                    'id' => 'id_option',
                    'name' => 'name',
                ],
            ],
            [
                'type' => 'select',
                'label' => $this->l('Kurier paczka w weekend dostępne do dnia'),
                'name' => 'INPOST_PAY_payment_courier_pww_to_day',
                'options' => [
                    'query' => $this->DayOptions(),
                    'id' => 'id_option',
                    'name' => 'name',
                ],
            ],
            [
                'type' => 'select',
                'label' => $this->l('Kurier paczka w weekend dostępne do godziny'),
                'name' => 'INPOST_PAY_payment_courier_pww_to_time',
                'options' => [
                    'query' => $this->TimeOptions(),
                    'id' => 'id_option',
                    'name' => 'name',
                ],
            ],
            [
                'type' => 'text',
                'label' => $this->l('Kurier pobranie netto'),
                'name' => 'INPOST_PAY_payment_courier_cod',
                'size' => 20,
            ],
            [
                'type' => 'select',
                'label' => $this->l('Kurier pobranie dostępne od dnia'),
                'name' => 'INPOST_PAY_payment_courier_cod_from_day',
                'options' => [
                    'query' => $this->DayOptions(),
                    'id' => 'id_option',
                    'name' => 'name',
                ],
            ],
            [
                'type' => 'select',
                'label' => $this->l('Kurier pobranie dostępne od godziny'),
                'name' => 'INPOST_PAY_payment_courier_cod_from_time',
                'options' => [
                    'query' => $this->TimeOptions(),
                    'id' => 'id_option',
                    'name' => 'name',
                ],
            ],
            [
                'type' => 'select',
                'label' => $this->l('Kurier pobranie dostępne do dnia'),
                'name' => 'INPOST_PAY_payment_courier_cod_to_day',
                'options' => [
                    'query' => $this->DayOptions(),
                    'id' => 'id_option',
                    'name' => 'name',
                ],
            ],
            [
                'type' => 'select',
                'label' => $this->l('Kurier pobranie dostępne do godziny'),
                'name' => 'INPOST_PAY_payment_courier_cod_to_time',
                'options' => [
                    'query' => $this->TimeOptions(),
                    'id' => 'id_option',
                    'name' => 'name',
                ],
            ],
            [
                'type' => 'select',
                'label' => $this->l('Paczkomat'),
                'name' => 'INPOST_PAY_payment_apm',
                'options' => [
                    'query' => $this->DeliveryOptions(),
                    'id' => 'id_option',
                    'name' => 'name',
                ],
            ],
            [
                'type' => 'text',
                'label' => $this->l('Paczkomat paczka w weekend netto'),
                'name' => 'INPOST_PAY_payment_apm_pww',
                'size' => 20,
            ],
            [
                'type' => 'select',
                'label' => $this->l('Paczkomat paczka w weekend dostępne od dnia'),
                'name' => 'INPOST_PAY_payment_apm_pww_from_day',
                'options' => [
                    'query' => $this->DayOptions(),
                    'id' => 'id_option',
                    'name' => 'name',
                ],
            ],
            [
                'type' => 'select',
                'label' => $this->l('Paczkomat paczka w weekend dostępne od godziny'),
                'name' => 'INPOST_PAY_payment_apm_pww_from_time',
                'options' => [
                    'query' => $this->TimeOptions(),
                    'id' => 'id_option',
                    'name' => 'name',
                ],
            ],
            [
                'type' => 'select',
                'label' => $this->l('Paczkomat paczka w weekend dostępne do dnia'),
                'name' => 'INPOST_PAY_payment_apm_pww_to_day',
                'options' => [
                    'query' => $this->DayOptions(),
                    'id' => 'id_option',
                    'name' => 'name',
                ],
            ],
            [
                'type' => 'select',
                'label' => $this->l('Paczkomat paczka w weekend dostępne do godziny'),
                'name' => 'INPOST_PAY_payment_apm_pww_to_time',
                'options' => [
                    'query' => $this->TimeOptions(),
                    'id' => 'id_option',
                    'name' => 'name',
                ],
            ],
            [
                'type' => 'text',
                'label' => $this->l('Paczkomat pobranie netto'),
                'name' => 'INPOST_PAY_payment_apm_cod',
                'size' => 20,
            ],
            [
                'type' => 'select',
                'label' => $this->l('Paczkomat pobranie dostępne od dnia'),
                'name' => 'INPOST_PAY_payment_apm_cod_from_day',
                'options' => [
                    'query' => $this->DayOptions(),
                    'id' => 'id_option',
                    'name' => 'name',
                ],
            ],
            [
                'type' => 'select',
                'label' => $this->l('Paczkomat pobranie dostępne od godziny'),
                'name' => 'INPOST_PAY_payment_apm_cod_from_time',
                'options' => [
                    'query' => $this->TimeOptions(),
                    'id' => 'id_option',
                    'name' => 'name',
                ],
            ],
            [
                'type' => 'select',
                'label' => $this->l('Paczkomat pobranie dostępne do dnia'),
                'name' => 'INPOST_PAY_payment_apm_cod_to_day',
                'options' => [
                    'query' => $this->DayOptions(),
                    'id' => 'id_option',
                    'name' => 'name',
                ],
            ],
            [
                'type' => 'select',
                'label' => $this->l('Paczkomat pobranie dostępne do godziny'),
                'name' => 'INPOST_PAY_payment_apm_cod_to_time',
                'options' => [
                    'query' => $this->TimeOptions(),
                    'id' => 'id_option',
                    'name' => 'name',
                ],
            ],
        ];

        return $fields;
    }

    protected function guiFormFields()
    {
        $fields = [];
        $translations = [
            'cart' => 'koszyk',
            'list' => 'lista',
            'details' => 'karta produktu',
        ];

        $translationsDirection = [
            'up' => 'Góra',
            'down' => 'Dół',
            'left' => 'Lewo',
            'right' => 'Prawo',
        ];
        foreach (['cart', 'details'] as $place) {
            $fields[] = [
                'type' => 'switch',
                'label' => $this->l('Wyświetlaj ' . $translations[$place]),
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
            ];
            if ($place == 'cart') {
                foreach (['up', 'down', 'left', 'right'] as $direction) {
                    $fields[] = [
                        'type' => 'text',
                        'label' => $this->l('Margines ' . $translationsDirection[$direction]),
                        'name' => 'INPOST_PAY_margin_' . $place . '_' . $direction,
                        'size' => 20,
                    ];
                }
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

        return $fields;
    }

    public function getContent()
    {
        $output = '';
        if (Tools::isSubmit('submit' . $this->name)) {
            foreach ($this->formFields() as $field) {
                $configValue = Tools::getValue($field['name']);
                if ($field['name'] == 'INPOST_PAY_client_secret' && $configValue == '*****') {
                    continue;
                }
                if (isset($field['multiple']) && $field['multiple'] == true) {
                    if (is_array($configValue)) {
                        Configuration::updateValue($field['name'], implode(',', $configValue));
                    } else {
                        Configuration::updateValue($field['name'], '');
                    }
                } else {
                    Configuration::updateValue($field['name'], $configValue);
                }
            }

            $output = $this->displayConfirmation($this->l('Settings updated'));
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
                    'title' => $this->l('Save'),
                    'class' => 'btn btn-default',
                ],
            ],
        ];

        $helper = new HelperForm();

        $helper->table = $this->table;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex . '&' . http_build_query(['configure' => $this->name]);
        $helper->submit_action = 'submit' . $this->name;
        $helper->default_form_language = (int) Configuration::get('PS_LANG_DEFAULT');

        foreach ($this->formFields() as $field) {
            if ($field['name'] == 'INPOST_PAY_client_secret') {
                $helper->tpl_vars['fields_value'][$field['name']] = \Configuration::get($field['name']) ? '*****' : '';
            } elseif (isset($field['multiple']) && $field['multiple'] == true) {
                $helper->tpl_vars['fields_value'][$field['name'] . '[]'] = explode(',', \Configuration::get($field['name']));
            } else {
                $helper->tpl_vars['fields_value'][$field['name']] = \Configuration::get($field['name']);
            }
        }

        return $helper->generateForm([$form]);
    }

    protected function EnvironmentOptions(): array
    {
        $options = [];
        if (defined('IZI_LOGGER')) {
            $options[] = [
                'id_option' => \izi\InPostIzi::ENVIRONMENT_DEVELOP,
                'name' => 'Deweloperskie',
            ];
        }
        $options[] = [
            'id_option' => \izi\InPostIzi::ENVIRONMENT_SANDBOX,
            'name' => 'Sandbox',
        ];
        $options[] = [
            'id_option' => \izi\InPostIzi::ENVIRONMENT_PRODUCTION,
            'name' => 'Produkcyjne',
        ];

        return $options;
    }

    protected function ShowOptions(): array
    {
        $options = [
            [
                'id_option' => 1,
                'name' => 'Testerom',
            ],
            [
                'id_option' => 2,
                'name' => 'Wszystkim',
            ],
        ];

        return $options;
    }

    protected function TermsOptions()
    {
        $options = [];
        foreach (CMS::getCMSPages((int) Configuration::get('PS_LANG_DEFAULT'), 1, true) as $page) {
            $options[] = [
                'id_option' => $this->context->link->getCMSLink($page['id_cms'], $page['link_rewrite']),
                'name' => $page['meta_title'],
            ];
        }

        return $options;
    }

    protected function StatusOptions()
    {
        $options = [];
        $states = new OrderState();
        foreach ($states->getOrderStates((int) Configuration::get('PS_LANG_DEFAULT')) as $status) {
            $options[] = [
                'id_option' => $status['id_order_state'],
                'name' => $status['name'],
            ];
        }

        return $options;
    }

    protected function DeliveryOptions()
    {
        $options = [];
        $table_name = '`' . _DB_PREFIX_ . 'carrier`';
        $carrierList = Db::getInstance()->executeS('select id_carrier, name from ' . $table_name . ' WHERE deleted = 0');
        foreach ($carrierList as $carrier) {
            $options[] = [
                'id_option' => $carrier['id_carrier'],
                'name' => $carrier['name'] . " ({$carrier['id_reference']})",
            ];
        }

        return $options;
    }

    protected function DayOptions()
    {
        $options = [];
        foreach (['Poniedziałek', 'Wtorek', 'Środa', 'Czwartek', 'Piątek', 'Sobota', 'Niedziela'] as $key => $name) {
            $options[] = [
                'id_option' => $key,
                'name' => $name,
            ];
        }

        return $options;
    }

    protected function TimeOptions()
    {
        $options = [];
        for ($i = 1; $i < 25; ++$i) {
            $options[] = [
                'id_option' => $i,
                'name' => $i . ':00',
            ];
        }

        return $options;
    }

    protected function AlignmentOptions(): array
    {
        $options = [
            [
                'id_option' => 'left',
                'name' => 'Do lewej',
            ],
            [
                'id_option' => 'center',
                'name' => 'Do środka',
            ],
            [
                'id_option' => 'right',
                'name' => 'Do prawej',
            ],
        ];

        return $options;
    }

    protected function BackgroundOptions(): array
    {
        $options = [
            [
                'id_option' => 'light',
                'name' => 'Jasne',
            ],
            [
                'id_option' => 'dark',
                'name' => 'Ciemne',
            ],
        ];

        return $options;
    }

    protected function VariantOptions(): array
    {
        $options = [
            [
                'id_option' => 'black',
                'name' => 'Czarny',
            ],
            [
                'id_option' => 'yellow',
                'name' => 'Żółty',
            ],
        ];

        return $options;
    }
}
