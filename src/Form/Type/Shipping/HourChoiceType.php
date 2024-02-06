<?php

declare(strict_types=1);

namespace izi\prestashop\Form\Type\Shipping;

use izi\prestashop\Configuration\DTO\Hour;
use izi\prestashop\Form\ChoiceList\HourChoiceLoader;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\ChoiceList\Loader\ChoiceLoaderInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class HourChoiceType extends AbstractType
{
    /**
     * @var \Context
     */
    private $context;

    /**
     * @var ChoiceLoaderInterface
     */
    private $choiceLoader;

    /**
     * @param HourChoiceLoader $choiceLoader
     */
    public function __construct(\Context $context, ChoiceLoaderInterface $choiceLoader)
    {
        $this->context = $context;
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
            'placeholder' => false,
            'choice_label' => function (Hour $hour): string {
                return $hour->getHour();
            },
        ]);
    }
}
