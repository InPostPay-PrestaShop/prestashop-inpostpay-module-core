<?php

declare(strict_types=1);

namespace izi\prestashop\Form\Type;

use izi\prestashop\Form\ChoiceList\OrderStateChoiceLoader;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\ChoiceList\Loader\ChoiceLoaderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

final class OrderStatusDescriptionMapType extends AbstractType
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

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var \OrderState $orderState */
        foreach ($this->choiceLoader->loadChoiceList()->getChoices() as $orderState) {
            $builder
                ->add($orderState->id, TextType::class, [
                    'required' => false,
                    'label' => $orderState->name[$this->context->language->id] ?? sprintf('Order state #%d', $orderState->id),
                    'attr' => [
                        'placeholder' => $orderState->name[$builder->getName()] ?? '',
                    ],
                ]);
        }
    }
}
