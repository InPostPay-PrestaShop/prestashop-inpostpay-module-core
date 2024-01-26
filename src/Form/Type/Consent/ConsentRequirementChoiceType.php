<?php

declare(strict_types=1);

namespace izi\prestashop\Form\Type\Consent;

use izi\prestashop\Common\Basket\ConsentRequirementType;
use izi\prestashop\Form\DataTransformer\EnumDataTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class ConsentRequirementChoiceType extends AbstractType
{
    private const TRANSLATION_SOURCE = 'consentrequirementchoicetype';

    /**
     * @var \Module
     */
    private $module;

    public function __construct(\Module $module)
    {
        $this->module = $module;
    }

    public function getParent(): string
    {
        return ChoiceType::class;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addModelTransformer(new EnumDataTransformer(ConsentRequirementType::class));
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'choices' => [
                $this->module->l('Optional', self::TRANSLATION_SOURCE) => ConsentRequirementType::Optional()->value,
                $this->module->l('Always required', self::TRANSLATION_SOURCE) => ConsentRequirementType::RequiredAlways()->value,
                $this->module->l('Required once', self::TRANSLATION_SOURCE) => ConsentRequirementType::RequiredOnce()->value,
            ],
        ]);
    }
}
