<?php

declare(strict_types=1);

namespace izi\prestashop\Form\Type\Consent;

use izi\prestashop\Configuration\DTO\Consent;
use izi\prestashop\ObjectModel\Repository\CmsPageRepository;
use izi\prestashop\ObjectModel\Repository\ObjectRepositoryInterface;
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
     * @var \Module
     */
    private $module;

    /**
     * @var \Context
     */
    private $context;

    /**
     * @var ObjectRepositoryInterface
     */
    private $cmsPageRepository;

    private $cmsChoices;

    /**
     * @param CmsPageRepository $cmsPageRepository
     */
    public function __construct(\Module $module, \Context $context, ObjectRepositoryInterface $cmsPageRepository)
    {
        $this->module = $module;
        $this->context = $context;
        $this->cmsPageRepository = $cmsPageRepository;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('cmsPageId', ChoiceType::class, [
                'choice_loader' => $this,
                'choice_value' => 'id',
                'choice_label' => 'meta_title',
                'label' => $this->module->l('Details page', self::TRANSLATION_SOURCE),
            ])
            ->add('descriptions', TranslatableType::class, [
                'label' => $this->module->l('Consent text in the mobile app', self::TRANSLATION_SOURCE),
                'type' => TextType::class,
            ])
            ->add('requirementType', ConsentRequirementChoiceType::class, [
                'label' => $this->module->l('Requiredness', self::TRANSLATION_SOURCE),
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
