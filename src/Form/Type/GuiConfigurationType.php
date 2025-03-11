<?php

declare(strict_types=1);

namespace izi\prestashop\Form\Type;

use izi\prestashop\Common\BindingPlace;
use izi\prestashop\Configuration\DTO;
use izi\prestashop\Configuration\GuiConfiguration;
use izi\prestashop\Configuration\GuiConfigurationInterface;
use izi\prestashop\Configuration\ProductRestrictionsProviderInterface;
use izi\prestashop\Form\Type\Widget\ProductPageDisplayConfigurationType;
use izi\prestashop\Form\Type\Widget\WidgetDisplayConfigurationType;
use izi\prestashop\Translation\LegacyTranslator;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class GuiConfigurationType extends AbstractType
{
    /**
     * @internal
     */
    public const TRANSLATION_SOURCE = 'guiconfigurationtype';

    private $translator;

    public function __construct(LegacyTranslator $translator)
    {
        $this->translator = $translator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var BindingPlace $bindingPlace */
        foreach ($options['binding_places'] as $bindingPlace) {
            $builder->add($bindingPlace->value, WidgetDisplayConfigurationType::class, [
                'label' => $bindingPlace->trans($this->translator),
                'property_path' => 'displayConfigurations[' . $bindingPlace->value . ']',
                'binding_place' => $bindingPlace,
                'description' => $this->getDescriptionByBindingPlace($bindingPlace),
            ]);
        }

        $builder
            ->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
                /** @var GuiConfigurationInterface|null $data */
                if (null === $data = $event->getData()) {
                    return;
                }

                $productConfiguration = $data->getDisplayConfiguration($bindingPlace = BindingPlace::ProductCard());

                if (!$productConfiguration instanceof ProductRestrictionsProviderInterface) {
                    return;
                }

                $form = $event->getForm();
                $form->add($bindingPlace->value, ProductPageDisplayConfigurationType::class, [
                    'label' => $bindingPlace->trans($this->translator),
                    'description' => $this->getDescriptionByBindingPlace($bindingPlace),
                    'property_path' => 'displayConfigurations[' . $bindingPlace->value . ']',
                ]);
            });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefaults([
                'data_class' => DTO\GuiConfiguration::class,
                'binding_places' => GuiConfiguration::getConfigurableBindingPlaces(),
            ])
            ->setAllowedTypes('binding_places', ['array'/*, BindingPlace::class . '[]'*/])
            ->setAllowedValues('binding_places', static function (array $value) {
                foreach ($value as $bindingPlace) {
                    if (!$bindingPlace instanceof BindingPlace) {
                        return false;
                    }

                    if (!$bindingPlace->canDisplayBindingWidget()) {
                        return false;
                    }
                }

                return true;
            });
    }

    private function getDescriptionByBindingPlace(BindingPlace $bindingPlace): string
    {
        switch ($bindingPlace) {
            case BindingPlace::ProductCard():
                return $this->translator->l('This widget will be displayed in the product page.', self::TRANSLATION_SOURCE);
            case BindingPlace::BasketSummary():
                return $this->translator->l('This widget will be displayed in the cart page, under submit button.', self::TRANSLATION_SOURCE);
            case BindingPlace::LoginPage():
                return $this->translator->l('This widget will be displayed in the login page, under form submit button.', self::TRANSLATION_SOURCE);
            case BindingPlace::RegisterFormPage():
                return $this->translator->l('This widget will be displayed in the register page, above register form.', self::TRANSLATION_SOURCE);
            case BindingPlace::CheckoutPage():
                return $this->translator->l('This widget will be displayed in the checkout page, above products summary.', self::TRANSLATION_SOURCE);
            case BindingPlace::MiniCartPage():
                return $this->translator->l('This widget will be displayed in the cart preview. To display this hook you have to register custom hook in your template "{hook h=\'displayIziCartPreviewButton\'}"', self::TRANSLATION_SOURCE);
            default:
                return '';
        }

        // translations moved to BindingPlace enum, kept for AdminModuleTranslations message discovery
        // ->l('Cart page', self::TRANSLATION_SOURCE),
        // ->l('Product card', self::TRANSLATION_SOURCE),
        // ->l('Login page', self::TRANSLATION_SOURCE),
        // ->l('Register page', self::TRANSLATION_SOURCE),
        // ->l('Checkout page', self::TRANSLATION_SOURCE),
        // ->l('Cart preview', self::TRANSLATION_SOURCE),
    }
}
