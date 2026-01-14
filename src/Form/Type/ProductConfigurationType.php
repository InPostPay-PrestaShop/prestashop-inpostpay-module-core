<?php

declare(strict_types=1);

namespace izi\prestashop\Form\Type;

use izi\prestashop\Configuration\DTO\ProductConfiguration;
use izi\prestashop\Form\Type\Image\ImageTypeChoiceType;
use izi\prestashop\Product\Image\ImageGalleryType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

final class ProductConfigurationType extends AbstractType
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('defaultImageGalleryType', EnumType::class, [
                'class' => ImageGalleryType::class,
                'label' => $this->translator->trans('Default image gallery type', [], 'Modules.Inpostizi.Product'),
                'help' => nl2br(implode("\n\n", [
                    $this->translator->trans('Determines what images are passed to the mobile app (applies to both cart and order products as well as hot products).', [], 'Modules.Inpostizi.Product'),
                    $this->translator->trans('You can override this setting for individual products using the product edit form.', [], 'Modules.Inpostizi.Product'),
                ])),
            ])
            ->add('normalImageTypeId', ImageTypeChoiceType::class, [
                'label' => $this->translator->trans('Product list image type', [], 'Modules.Inpostizi.Product'),
                'help' => $this->translator->trans('This image format will be used on the product list in the mobile app.', [], 'Modules.Inpostizi.Product'),
            ])
            ->add('smallImageTypeId', ImageTypeChoiceType::class, [
                'label' => $this->translator->trans('Product details image type', [], 'Modules.Inpostizi.Product'),
                'help' => $this->translator->trans('This image format will be used in the product details view in the mobile app.', [], 'Modules.Inpostizi.Product'),
            ])
            ->add('largeImageTypeId', ImageTypeChoiceType::class, [
                'label' => $this->translator->trans('Large image type', [], 'Modules.Inpostizi.Product'),
                'help' => $this->translator->trans('This image format will be used when using the zoom option in the mobile app.', [], 'Modules.Inpostizi.Product'),
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ProductConfiguration::class,
        ]);
    }
}
