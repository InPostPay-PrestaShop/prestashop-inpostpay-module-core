<?php
/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/OSL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to https://devdocs.prestashop.com/ for more information.
 *
 * @author    PrestaShop SA and Contributors <contact@prestashop.com>
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 */

namespace izi\prestashop\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormErrorIterator;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Polyfill for PS < 1.7.6.
 *
 * @see \PrestaShopBundle\Form\Admin\Type\TranslatableType
 */
class TranslatableType extends AbstractType
{
    /**
     * @var array List of enabled locales
     */
    private $enabledLocales;

    /**
     * @var array List of all available locales
     */
    private $availableLocales;

    /**
     * @var int default form language ID
     */
    private $defaultFormLanguageId;

    public function __construct(\Context $context = null, array $availableLocales = null)
    {
        $context = $context ?? \Context::getContext();

        $this->availableLocales = $availableLocales ?? \Language::getLanguages(false);
        $this->enabledLocales = $this->filterEnabledLocales($this->availableLocales);
        $this->defaultFormLanguageId = $context->language->id;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        foreach ($options['locales'] as $locale) {
            $typeOptions = $options['options'];
            $typeOptions['label'] = $locale['iso_code'];

            if (!isset($typeOptions['required'])) {
                $typeOptions['required'] = false;
            }

            $builder->add($locale['id_lang'], $options['type'], $typeOptions);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $errors = iterator_to_array($view->vars['errors']);

        $errorsByLocale = $this->getErrorsByLocale($form, $options['locales']);

        if ($errorsByLocale !== null) {
            foreach ($errorsByLocale as $errorByLocale) {
                $errors[] = new FormError(sprintf('%s: %s', $errorByLocale['locale_name'], $errorByLocale['error_message']));
            }
        }

        /** @var FormInterface $varsForm */
        $varsForm = $view->vars['errors']->getForm();
        $view->vars['errors'] = new FormErrorIterator($varsForm, $errors);
        $view->vars['locales'] = $options['locales'];
        $view->vars['default_locale'] = $this->getDefaultLocale($options['locales']);
        $view->vars['hide_locales'] = 1 >= count($options['locales']);
        $view->vars['use_tabs'] = !empty($options['use_tabs']) && !empty($options['use_dropdown']);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'type' => TextType::class,
            'options' => [],
            'error_bubbling' => false,
            'only_enabled_locales' => false,
            'locales' => function (Options $options) {
                return $options['only_enabled_locales']
                    ? $this->enabledLocales
                    : $this->availableLocales;
            },
            // These two options allow to override the default choice of the component between tab and dropdown (by
            // default it is based on input type being a textarea)
            'use_tabs' => null,
            'use_dropdown' => null,
        ]);

        $resolver->setAllowedTypes('locales', 'array');
        $resolver->setAllowedTypes('options', 'array');
        $resolver->setAllowedTypes('type', 'string');
        $resolver->setAllowedTypes('error_bubbling', 'bool');
        $resolver->setAllowedTypes('use_tabs', ['null', 'bool']);
        $resolver->setAllowedTypes('use_dropdown', ['null', 'bool']);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix(): string
    {
        return 'inpostizi_translatable';
    }

    /**
     * If there are more than one locale it gets nested errors and if found prepares the errors for usage in twig.
     * If there are only one error which is not assigned to the default language then the error is being localised.
     */
    private function getErrorsByLocale(FormInterface $form, array $locales): ?array
    {
        $formErrors = $form->getErrors(true);

        if (0 === $formErrors->count()) {
            return null;
        }

        if (1 === $formErrors->count()) {
            $errorByLocale = $this->getSingleTranslatableErrorExcludingDefaultLocale(
                $formErrors,
                $form,
                $locales
            );

            if (!$errorByLocale) {
                return null;
            }

            return [$errorByLocale];
        }

        return $this->getTranslatableErrors(
            $formErrors,
            $form,
            $locales
        );
    }

    /**
     * Gets single error excluding the default locales error since for default locale a language name prefix is not
     * required.
     */
    private function getSingleTranslatableErrorExcludingDefaultLocale(FormErrorIterator $formErrors, FormInterface $form, array $locales): ?array
    {
        $errorByLocale = null;
        $formError = $formErrors[0];
        $nonDefaultLanguageFormKey = null;
        $iteration = 0;

        foreach ($form as $formItem) {
            if ($this->doesErrorFormAndCurrentFormMatches($formError->getOrigin(), $formItem)) {
                $nonDefaultLanguageFormKey = $iteration;

                break;
            }

            ++$iteration;
        }

        if (isset($locales[$nonDefaultLanguageFormKey])) {
            $errorByLocale = [
                'locale_name' => $locales[$nonDefaultLanguageFormKey]['name'],
                'error_message' => $formError->getMessage(),
            ];
        }

        return $errorByLocale;
    }

    /**
     * Gets translatable errors ready for popover display and assigned to each language.
     */
    private function getTranslatableErrors(FormErrorIterator $formErrors, FormInterface $form, array $locales): ?array
    {
        $errorsByLocale = null;
        $iteration = 0;
        foreach ($form as $formItem) {
            $doesLocaleExistForInvalidForm = isset($locales[$iteration])
                && $formItem->isSubmitted()
                && !$formItem->isValid();

            if ($doesLocaleExistForInvalidForm) {
                foreach ($formErrors as $formError) {
                    if ($this->doesErrorFormAndCurrentFormMatches($formError->getOrigin(), $formItem)) {
                        $errorsByLocale[] = [
                            'locale_name' => $locales[$iteration]['name'],
                            'error_message' => $formError->getMessage(),
                        ];
                    }
                }
            }

            ++$iteration;
        }

        return $errorsByLocale;
    }

    /**
     * Determines if the error form matches the given form. Used for mapping the locales for the form fields.
     */
    private function doesErrorFormAndCurrentFormMatches(FormInterface $errorForm, FormInterface $currentForm): bool
    {
        return $errorForm === $currentForm;
    }

    /**
     * Get default locale.
     */
    private function getDefaultLocale(array $locales): array
    {
        foreach ($locales as $locale) {
            if ($this->defaultFormLanguageId === (int) $locale['id_lang']) {
                return $locale;
            }
        }

        return reset($locales);
    }

    /**
     * Filters only enabled locales
     */
    private function filterEnabledLocales(array $availableLocales): array
    {
        return array_filter($availableLocales, static function (array $locale): bool {
            return $locale['active'];
        });
    }
}
