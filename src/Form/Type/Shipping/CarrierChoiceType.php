<?php

declare(strict_types=1);

namespace izi\prestashop\Form\Type\Shipping;

use izi\prestashop\Form\ChoiceList\CarrierChoiceLoader;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\ChoiceList\Loader\ChoiceLoaderInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Carrier by reference ID choice form.
 */
final class CarrierChoiceType extends AbstractType
{
    /**
     * @var CarrierChoiceLoader
     */
    private $choiceLoader;

    /**
     * @param CarrierChoiceLoader $choiceLoader
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
            'choice_value' => 'id_reference',
            'choice_label' => 'name',
        ]);
    }
}
