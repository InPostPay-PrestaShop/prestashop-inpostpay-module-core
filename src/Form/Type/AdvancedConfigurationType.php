<?php

declare(strict_types=1);

namespace izi\prestashop\Form\Type;

use izi\prestashop\Configuration\DTO\AdvancedConfiguration;
use PrestaShopBundle\Form\Admin\Type\SwitchType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class AdvancedConfigurationType extends AbstractType
{
    private const TRANSLATION_SOURCE = 'advancedconfigurationtype';

    private $module;

    public function __construct(\Module $module)
    {
        $this->module = $module;
    }


    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('debugEnabled', SwitchType::class, [
                'required' => false,
                'choices' => [
                    $this->module->l('Enable debug mode', self::TRANSLATION_SOURCE) => false,
                    $this->module->l('Disable debug mode', self::TRANSLATION_SOURCE) => true,
                ],
            ]);

    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => AdvancedConfiguration::class,
        ]);
    }
}
