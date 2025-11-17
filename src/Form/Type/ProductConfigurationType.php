<?php

declare(strict_types=1);

namespace izi\prestashop\Form\Type;

use izi\prestashop\Configuration\DTO\ProductConfiguration;
use izi\prestashop\Form\Type\Image\ImageTypeChoiceType;
use izi\prestashop\Product\Image\ImageGalleryType;
use izi\prestashop\Translation\LegacyTranslator;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class ProductConfigurationType extends AbstractType
{
    /**
     * @internal
     */
    public const TRANSLATION_SOURCE = 'productconfigurationtype';

    private $translator;

    public function __construct(LegacyTranslator $translator)
    {
        $this->translator = $translator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('defaultImageGalleryType', EnumType::class, [
                'class' => ImageGalleryType::class,
                'label' => $this->translator->l('Default image gallery type', self::TRANSLATION_SOURCE),
                'help' => nl2br(implode("\n\n", [
                    $this->translator->l('Determines what images are passed to the mobile app (applies to both cart and order products as well as hot products).', self::TRANSLATION_SOURCE),
                    $this->translator->l('You can override this setting for individual products using the product edit form.', self::TRANSLATION_SOURCE),
                ])),
            ])
            ->add('normalImageTypeId', ImageTypeChoiceType::class, [
                'label' => $this->translator->l('Product list image type', self::TRANSLATION_SOURCE),
                'help' => $this->translator->l('This image format will be used in the product list in the application.', self::TRANSLATION_SOURCE),
            ])
            ->add('smallImageTypeId', ImageTypeChoiceType::class, [
                'label' => $this->translator->l('Detail image type', self::TRANSLATION_SOURCE),
                'help' => $this->translator->l('This image format will be used in the product details info in the application.', self::TRANSLATION_SOURCE),
            ])
            ->add('largeImageTypeId', ImageTypeChoiceType::class, [
                'label' => $this->translator->l('Large image type', self::TRANSLATION_SOURCE),
                'help' => $this->translator->l('This image format will be used in the image zoom in the application.', self::TRANSLATION_SOURCE),
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ProductConfiguration::class,
        ]);
    }
}
