<?php

declare(strict_types=1);

namespace izi\prestashop\Form\Type\Image;

use izi\prestashop\Form\ChoiceList\ProductImageTypeChoiceLoader;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\ChoiceList\Loader\ChoiceLoaderInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class ImageTypeChoiceType extends AbstractType
{
    /**
     * @var ChoiceLoaderInterface
     */
    private $choiceLoader;

    /**
     * @param ProductImageTypeChoiceLoader $choiceLoader
     */
    public function __construct(ChoiceLoaderInterface $choiceLoader)
    {
        $this->choiceLoader = $choiceLoader;
    }

    public function getParent(): string
    {
        return ChoiceType::class;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'choice_loader' => $this->choiceLoader,
            'choice_value' => 'id',
            'choice_label' => function (\ImageType $imageType): string {
                $name = $imageType->name ?? '';

                if ($imageType->width && $imageType->height) {
                    $name .= sprintf(' (%d x %d)', $imageType->width, $imageType->height);
                }

                return $name;
            },
        ]);
    }
}
