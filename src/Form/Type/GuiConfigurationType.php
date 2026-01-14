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
use izi\prestashop\Hook\Front\DisplayIziCartPreviewButton;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

final class GuiConfigurationType extends AbstractType
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(TranslatorInterface $translator)
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
            ->setAllowedTypes('binding_places', [BindingPlace::class . '[]'])
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
                return $this->translator->trans('The widget will be displayed on the product page.', [], 'Modules.Inpostizi.Gui');
            case BindingPlace::BasketSummary():
                return $this->translator->trans('The widget will be displayed on the cart page, below the "{button}" button.', [
                    '{button}' => $this->translator->trans('Proceed to checkout', [], 'Shop.Theme.Actions'),
                ], 'Modules.Inpostizi.Gui');
            case BindingPlace::LoginPage():
                return $this->translator->trans('The widget will be displayed on the login page, below the login form.', [], 'Modules.Inpostizi.Gui');
            case BindingPlace::RegisterFormPage():
                return $this->translator->trans('The widget will be displayed on the registration page, above the registration form.', [], 'Modules.Inpostizi.Gui');
            case BindingPlace::CheckoutPage():
                return $this->translator->trans('The widget will be displayed on the checkout page, above the order summary.', [], 'Modules.Inpostizi.Gui');
            case BindingPlace::MiniCartPage():
                return $this->translator->trans('The widget will be displayed in the cart preview. To use this location, a custom hook call must be added in the template file: `{code}`.', [
                    '{code}' => \sprintf('{hook h="%s"}', DisplayIziCartPreviewButton::HOOK_NAME),
                ], 'Modules.Inpostizi.Gui');
            default:
                return '';
        }
    }
}
