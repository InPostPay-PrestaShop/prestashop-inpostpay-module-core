<?php

declare(strict_types=1);

namespace izi\prestashop\ProductOptions\Form;

use izi\prestashop\Configuration\ProductConfigurationInterface;
use izi\prestashop\Form\Type\EnumType;
use izi\prestashop\Product\Image\ImageGalleryType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

final class ProductOptionsType extends AbstractType
{
    /**
     * @var ProductConfigurationInterface
     */
    private $configuration;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(ProductConfigurationInterface $configuration, TranslatorInterface $translator)
    {
        $this->configuration = $configuration;
        $this->translator = $translator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $galleryType = $this->configuration->getDefaultImageGalleryType();

        $builder
            ->add('imageGalleryType', EnumType::class, [
                'class' => ImageGalleryType::class,
                'required' => false,
                'label' => $this->translator->trans('Image gallery type', [], 'Modules.Inpostizi.Product'),
                'placeholder' => vsprintf('%s (%s)', [
                    $this->translator->trans('Use default behavior', [], 'Admin.Catalog.Feature'),
                    $galleryType->trans($this->translator),
                ]),
                'help' => $this->translator->trans('Determines what images are passed to the mobile app (applies to both cart and order products as well as hot products).', [], 'Modules.Inpostizi.Product'),
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'label' => $this->translator->trans('InPost Pay options', [], 'Modules.Inpostizi.Product'),
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'inpostizi_product_options';
    }
}
