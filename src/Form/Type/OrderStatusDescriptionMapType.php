<?php

declare(strict_types=1);

namespace izi\prestashop\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\ChoiceList\Loader\ChoiceLoaderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

final class OrderStatusDescriptionMapType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        foreach ($this->getOrderStates($builder) as $orderState) {
            $builder->add((string) $orderState->id, TextType::class, [
                'required' => false,
                'label' => $orderState->name ?? \sprintf('Order state #%d', $orderState->id),
                'attr' => [
                    'placeholder' => $orderState->name ?? '',
                ],
            ]);
        }
    }

    /**
     * @return \OrderState[]
     */
    private function getOrderStates(FormBuilderInterface $builder): array
    {
        /** @var ChoiceLoaderInterface $choiceLoader */
        $choiceLoader = $builder->getFormFactory()->createBuilder(ObjectModelType::class, null, [
            'class' => \OrderState::class,
        ])->getFormConfig()->getOption('choice_loader');

        return $choiceLoader->loadChoiceList()->getChoices();
    }
}
