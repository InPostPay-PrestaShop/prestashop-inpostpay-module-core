<?php

declare(strict_types=1);

namespace izi\prestashop\Form\Type\Consent;

use izi\prestashop\Configuration\DTO\Consent;
use izi\prestashop\Form\Type\TranslatableType as TranslatableTypePolyfill;
use izi\prestashop\ObjectModel\Repository\CmsPageRepository;
use izi\prestashop\ObjectModel\Repository\ObjectRepositoryInterface;
use izi\prestashop\Translation\LegacyTranslator;
use PrestaShopBundle\Form\Admin\Type\TranslatableType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\ChoiceList\ArrayChoiceList;
use Symfony\Component\Form\ChoiceList\ChoiceListInterface;
use Symfony\Component\Form\ChoiceList\Loader\ChoiceLoaderInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class ConsentType extends AbstractType implements ChoiceLoaderInterface
{
    private const TRANSLATION_SOURCE = 'consenttype';

    /**
     * @var LegacyTranslator
     */
    private $translator;

    /**
     * @var \Context
     */
    private $context;

    /**
     * @var CmsPageRepository
     */
    private $cmsPageRepository;

    private $cmsChoices;

    /**
     * @param CmsPageRepository $cmsPageRepository
     */
    public function __construct(LegacyTranslator $translator, \Context $context, ObjectRepositoryInterface $cmsPageRepository)
    {
        $this->translator = $translator;
        $this->context = $context;
        $this->cmsPageRepository = $cmsPageRepository;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $translatableClass = class_exists(TranslatableType::class)
            ? TranslatableType::class
            : TranslatableTypePolyfill::class;

        $builder
            ->add('cmsPageId', ChoiceType::class, [
                'choice_loader' => $this,
                'choice_value' => 'id',
                'choice_label' => 'meta_title',
                'label' => $this->translator->l('Details page', self::TRANSLATION_SOURCE),
                'help' => $this->translator->l('Specifies the page to which your customer will be redirected for a target who clicks on a given consent in the InPost mobile app.', self::TRANSLATION_SOURCE),
            ])
            ->add('descriptions', $translatableClass, [
                'label' => $this->translator->l('Consent text in the mobile app', self::TRANSLATION_SOURCE),
                'type' => TextType::class,
                'help' => sprintf(
                    '%s %s',
                    $this->translator->l('Add a description to be displayed with the consent in the InPost mobile app.', self::TRANSLATION_SOURCE),
                    $this->translator->l('If blank in a language other than the shop\'s default, the value for the default language will be used.', self::TRANSLATION_SOURCE)
                ),
            ])
            ->add('requirementType', ConsentRequirementChoiceType::class, [
                'label' => $this->translator->l('Requiredness', self::TRANSLATION_SOURCE),
                'help' => $this->translator->l('Specify whether consent is required or optional.', self::TRANSLATION_SOURCE),
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Consent::class,
        ]);
    }

    private function getCmsPageChoices(): array
    {
        if (isset($this->cmsChoices)) {
            return $this->cmsChoices;
        }

        $this->cmsChoices = [];

        $languageId = (int) $this->context->language->id;
        $shopId = (int) $this->context->shop->id;

        foreach ($this->cmsPageRepository->findActiveByLanguageAndShopId($languageId, $shopId) as $cmsPage) {
            $this->cmsChoices[$cmsPage->id] = $cmsPage;
        }

        return $this->cmsChoices;
    }

    /**
     * {@inheritDoc}
     */
    public function loadChoiceList($value = null): ChoiceListInterface
    {
        return new ArrayChoiceList($this->getCmsPageChoices(), $value);
    }

    /**
     * {@inheritDoc}
     */
    public function loadChoicesForValues(array $values, $value = null): array
    {
        return $this->loadChoiceList($value)->getChoicesForValues($values);
    }

    /**
     * {@inheritDoc}
     */
    public function loadValuesForChoices(array $choices, $value = null): array
    {
        $choices = array_map(function ($choice): ?\CMS {
            if ($choice instanceof \CMS) {
                return $choice;
            }

            return $this->getCmsPageChoices()[$choice] ?? null;
        }, $choices);

        return $this->loadChoiceList($value)->getValuesForChoices($choices);
    }
}
