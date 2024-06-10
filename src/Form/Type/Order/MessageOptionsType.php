<?php

declare(strict_types=1);

namespace izi\prestashop\Form\Type\Order;

use izi\prestashop\Configuration\DTO\Order\MessageOptions;
use izi\prestashop\Form\Type\SwitchType as SwitchTypePolyfill;
use izi\prestashop\Order\Message\ParameterDescriptorInterface;
use izi\prestashop\Translation\LegacyTranslator;
use PrestaShopBundle\Form\Admin\Type\SwitchType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @internal
 */
final class MessageOptionsType extends AbstractType
{
    private const TRANSLATION_SOURCE = 'messageoptionstype';

    /**
     * @var LegacyTranslator
     */
    private $translator;

    /**
     * @var ParameterDescriptorInterface
     */
    private $parameterDescriptor;

    public function __construct(LegacyTranslator $translator, ParameterDescriptorInterface $parameterDescriptor)
    {
        $this->translator = $translator;
        $this->parameterDescriptor = $parameterDescriptor;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $switchClass = class_exists(SwitchType::class)
            ? SwitchType::class
            : SwitchTypePolyfill::class;

        $builder
            ->add('appendIfApmDelivery', $switchClass, [
                'label' => $this->translator->l('Append custom content to customer message if APM delivery was selected', self::TRANSLATION_SOURCE),
            ])
            ->add('message', TextareaType::class, [
                'required' => false,
                'label' => $this->translator->l('Message format', self::TRANSLATION_SOURCE),
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

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => MessageOptions::class,
        ]);
    }

    private function getMessageFormatDescription(): string
    {
        return sprintf(
            "%s:\n%s\n\n%s\n\n%s",
            $this->translator->l('Available parameters', self::TRANSLATION_SOURCE),
            $this->formatParameterDescriptions(),
            $this->translator->l('Expressions enclosed in double curly braces are evaluated (e.g. `{{ is_pww ? "yes" : "no" }}` will result in "yes" if Weekend Delivery option was selected or "no" otherwise).', self::TRANSLATION_SOURCE),
            $this->translator->l('More details can be found in module documentation.', self::TRANSLATION_SOURCE)
        );
    }

    private function formatParameterDescriptions(): string
    {
        $descriptions = [];

        foreach ($this->parameterDescriptor->getDescriptions() as $name => $description) {
            $descriptions[] = sprintf('{%s} - %s', $name, $description);
        }

        return implode("\n", $descriptions);
    }
}
