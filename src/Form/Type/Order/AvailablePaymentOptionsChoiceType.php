<?php

declare(strict_types=1);

namespace izi\prestashop\Form\Type\Order;

use izi\prestashop\Common\PaymentType;
use izi\prestashop\Form\ChoiceList\AvailablePaymentOptionChoiceLoader;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\ChoiceList\Loader\ChoiceLoaderInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @deprecated since 3.4.0
 */
final class AvailablePaymentOptionsChoiceType extends AbstractType
{
    /**
     * @var ChoiceLoaderInterface
     */
    private $choiceLoader;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @param AvailablePaymentOptionChoiceLoader $choiceLoader
     */
    public function __construct(ChoiceLoaderInterface $choiceLoader, TranslatorInterface $translator)
    {
        @trigger_error(sprintf('Class "%s" is deprecated since 3.4.0.', self::class), \E_USER_DEPRECATED);
        $this->choiceLoader = $choiceLoader;
        $this->translator = $translator;
    }

    public function getParent(): string
    {
        return ChoiceType::class;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if (!$options['multiple'] || null === $options['choice_loader']) {
            return;
        }

        /** @var ChoiceLoaderInterface $choiceLoader */
        $choiceLoader = $options['choice_loader'];

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) use ($choiceLoader) {
            if (null === $data = $event->getData()) {
                return;
            }

            if (!\is_array($data)) {
                throw new TransformationFailedException('Expected an array.');
            }

            $choices = $choiceLoader->loadChoiceList()->getChoices();

            // remove valid but unavailable payment type choices
            $data = array_filter($data, static function ($value) use ($choices) {
                if (!\is_string($value)) {
                    return true;
                }

                $type = PaymentType::from($value);

                return \in_array($type, $choices, true);
            });

            $event->setData($data);
        }, 128);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'choice_loader' => $this->choiceLoader,
            'choice_value' => function (PaymentType $paymentType) {
                return $paymentType->value;
            },
            'choice_label' => function (PaymentType $paymentType) {
                return $paymentType->trans($this->translator);
            },
        ]);
    }
}
