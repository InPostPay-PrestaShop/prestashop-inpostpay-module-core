<?php

declare(strict_types=1);

namespace izi\prestashop\Form\Type\Order;

use izi\prestashop\Configuration\DTO\Order\MessageOptions;
use izi\prestashop\Order\Message\ParameterDescriptorInterface;
use PrestaShopBundle\Form\Admin\Type\SwitchType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @internal
 */
final class MessageOptionsType extends AbstractType
{
    private const DOCS_URL = 'https://dokumentacja-inpost.atlassian.net/wiki/spaces/PL/pages/925564940/InPost+Pay+-+PrestaShop+Widget+2.0#Komentarz-zamówienia';

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var ParameterDescriptorInterface
     */
    private $parameterDescriptor;

    public function __construct(TranslatorInterface $translator, ParameterDescriptorInterface $parameterDescriptor)
    {
        $this->translator = $translator;
        $this->parameterDescriptor = $parameterDescriptor;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('appendIfApmDelivery', SwitchType::class, [
                'label' => $this->translator->trans('Append custom content to customer message if APM delivery was selected', [], 'Modules.Inpostizi.Order'),
            ])
            ->add('message', TextareaType::class, [
                'required' => false,
                'label' => $this->translator->trans('Message format', [], 'Modules.Inpostizi.Order'),
                'help' => nl2br($this->getMessageFormatDescription()),
                'attr' => [
                    'rows' => 5,
                ],
            ])
            ->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
                $data = $event->getData();

                if ($data instanceof MessageOptions && $data->isCustomFormat()) {
                    $event->getForm()->remove('appendIfApmDelivery');
                }
            });
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['details'] = strtr(htmlspecialchars($this->translator->trans('Click {link} to go to the module documentation and read full instructions on how to configure the message format.', [], 'Modules.Inpostizi.Order')), [
            '{link}' => \sprintf('<a href="%s" target="_blank">%s</a>', self::DOCS_URL, htmlspecialchars($this->translator->trans('here', [], 'Modules.Inpostizi.Order'))),
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => MessageOptions::class,
        ]);
    }

    private function getMessageFormatDescription(): string
    {
        return \sprintf(
            "%s:\n%s\n\n%s\n\n%s",
            $this->translator->trans('Available parameters', [], 'Modules.Inpostizi.Order'),
            $this->formatParameterDescriptions(),
            $this->translator->trans('Expressions enclosed in double curly braces are evaluated (e.g. `{conditional}` will result in "{if_true}" if {service} option was selected or "{if_false}" otherwise).', [
                '{conditional}' => '{{ is_pww ? "yes" : "no" }}',
                '{if_true}' => 'yes',
                '{if_false}' => 'no',
                '{service}' => $this->translator->trans('Weekend Delivery', [], 'Modules.Inpostizi.Delivery'),
            ], 'Modules.Inpostizi.Order'),
            $this->translator->trans('More details can be found in the module documentation.', [], 'Modules.Inpostizi.Order')
        );
    }

    private function formatParameterDescriptions(): string
    {
        $descriptions = [];

        foreach ($this->parameterDescriptor->getDescriptions() as $name => $description) {
            $descriptions[] = \sprintf('{%s} - %s', $name, $description);
        }

        return implode("\n", $descriptions);
    }
}
