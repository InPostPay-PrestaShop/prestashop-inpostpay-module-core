<?php

declare(strict_types=1);

namespace izi\prestashop\Form\Type\Widget;

use izi\prestashop\Common\BindingPlace;
use izi\prestashop\Configuration\DTO\WidgetDisplayConfiguration;
use PrestaShopBundle\Form\Admin\Type\SwitchType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

final class WidgetDisplayConfigurationType extends AbstractType
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['description'] = $options['description'];
        $view->vars['binding_place'] = $options['binding_place'];
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('displayed', SwitchType::class, [
                'required' => false,
                'label' => $this->translator->trans('Displayed', [], 'Modules.Inpostizi.Gui'),
                'help' => $this->translator->trans('In order to increase conversions, we recommend displaying InPost Pay on both the shopping cart tab and the product tab.', [], 'Modules.Inpostizi.Gui'),
            ])
            ->add('htmlStyles', HtmlStylesType::class, [
                'label' => false,
            ])
            ->addEventListener(FormEvents::PRE_SET_DATA, static function (FormEvent $event) {
                $data = $event->getData();
                $event->getForm()->add('widgetConfiguration', WidgetConfigurationType::class, [
                    'label' => false,
                    'preview_container_styles' => $data instanceof WidgetDisplayConfiguration ? $data->getHtmlStyles() : [],
                ]);
            });

        $builder->get('htmlStyles')->addEventListener(FormEvents::POST_SUBMIT, static function (FormEvent $event) {
            $data = $event->getData();
            $event->getForm()->getParent()->add('widgetConfiguration', WidgetConfigurationType::class, [
                'label' => false,
                'preview_container_styles' => $data ?? [],
            ]);
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setRequired('binding_place')
            ->setDefaults([
                'data_class' => WidgetDisplayConfiguration::class,
                'empty_data' => static function (FormInterface $form) {
                    $bindingPlace = $form->getConfig()->getOption('binding_place');

                    return WidgetDisplayConfiguration::for($bindingPlace);
                },
                'description' => '',
            ])
            ->setAllowedTypes('binding_place', BindingPlace::class)
            ->setAllowedTypes('description', 'string');
    }
}
