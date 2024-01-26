<?php

declare(strict_types=1);

namespace izi\prestashop\Form\Type;

use izi\prestashop\Form\ChoiceList\OrderStateChoiceLoader;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\ChoiceList\Loader\ChoiceLoaderInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class OrderStateChoiceType extends AbstractType
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
     * @param OrderStateChoiceLoader $choiceLoader
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
            'choice_value' => static function ($orderState): int {
                return (int) $orderState->id;
            },
            'choice_label' => function (\OrderState $orderState): string {
                return $orderState->name[$this->context->language->id] ?? '';
            },
        ]);
    }
}
