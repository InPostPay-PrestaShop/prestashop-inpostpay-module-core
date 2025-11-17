<?php

declare(strict_types=1);

namespace izi\prestashop\ProductOptions\Form;

use izi\prestashop\Configuration\ProductConfiguration;
use izi\prestashop\Configuration\ProductConfigurationInterface;
use izi\prestashop\Form\Type\EnumType;
use izi\prestashop\Form\Type\ProductConfigurationType;
use izi\prestashop\Product\Image\ImageGalleryType;
use izi\prestashop\Translation\LegacyTranslator;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class ProductOptionsType extends AbstractType
{
    private const TRANSLATION_SOURCE = 'productoptionstype';

    /**
     * @var ProductConfigurationInterface
     */
    private $configuration;

    /**
     * @var LegacyTranslator
     */
    private $translator;

    /**
     * @var \Context
     */
    private $context;

    public function __construct(ProductConfigurationInterface $configuration, LegacyTranslator $translator, \Context $context)
    {
        $this->configuration = $configuration;
        $this->translator = $translator;
        $this->context = $context;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $galleryType = ProductConfiguration::getDefaultImageGalleryTypeFromConfig($this->configuration);

        $builder
            ->add('imageGalleryType', EnumType::class, [
                'class' => ImageGalleryType::class,
                'required' => false,
                'label' => $this->translator->l('Image gallery type', self::TRANSLATION_SOURCE),
                'placeholder' => vsprintf('%s (%s)', [
                    $this->context->getTranslator()->trans('Use default behavior', [], 'Admin.Catalog.Feature'),
                    $galleryType->trans($this->translator),
                ]),
                'help' => $this->translator->l('Determines what images are passed to the mobile app (applies to both cart and order products as well as hot products).', ProductConfigurationType::TRANSLATION_SOURCE),
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'label' => $this->translator->l('InPost Pay options', self::TRANSLATION_SOURCE),
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'inpostizi_product_options';
    }
}
