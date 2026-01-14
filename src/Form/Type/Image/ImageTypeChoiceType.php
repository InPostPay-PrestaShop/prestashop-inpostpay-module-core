<?php

declare(strict_types=1);

namespace izi\prestashop\Form\Type\Image;

use izi\prestashop\Form\Type\ObjectModelType;
use izi\prestashop\ObjectModel\Repository\ObjectRepositoryInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class ImageTypeChoiceType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'class' => \ImageType::class,
            'query_builder' => function (ObjectRepositoryInterface $repository) {
                return $repository->createQueryBuilder('t')
                    ->where('t.products = 1')
                    ->orderBy('t.width, t.height');
            },
            'choice_label' => function (\ImageType $imageType): string {
                $name = $imageType->name ?? '';

                if ($imageType->width && $imageType->height) {
                    $name .= \sprintf(' (%d x %d)', $imageType->width, $imageType->height);
                }

                return $name;
            },
            'input' => 'id',
        ]);
    }

    public function getParent(): string
    {
        return ObjectModelType::class;
    }
}
