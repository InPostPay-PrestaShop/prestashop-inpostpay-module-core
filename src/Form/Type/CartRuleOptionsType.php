<?php

declare(strict_types=1);

namespace izi\prestashop\Form\Type;

use PrestaShopBundle\Form\Admin\Type\SwitchType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

final class CartRuleOptionsType extends AbstractType
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
        $builder
            ->add('omnibus', SwitchType::class, [
                'label' => $this->translator->trans('Falls under the Omnibus Directive', [], 'Modules.Inpostizi.Promo'),
            ])
            ->add('promoDetailsPageId', CmsPageChoiceType::class, [
                'required' => false,
                'input' => 'id',
                'label' => $this->translator->trans('Promotion details page', [], 'Modules.Inpostizi.Promo'),
                'placeholder' => $this->translator->trans('Use the default page', [], 'Modules.Inpostizi.Promo'),
                'help' => implode("\n", [
                    $this->translator->trans('In order for the available promotion data to be passed to the mobile app, the "{option}" option must be enabled for the cart rule.', [
                        '{option}' => $this->translator->trans('Highlight', [], 'Admin.Catalog.Feature'),
                    ], 'Modules.Inpostizi.Promo'),
                    $this->translator->trans('If neither the default value nor the cart rule specific value is configured, the available promotion data will not be passed to the mobile app.', [], 'Modules.Inpostizi.Promo'),
                    $this->translator->trans('The default page can be selected on the module configuration page.', [], 'Modules.Inpostizi.Promo'),
                ]),
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'label' => $this->translator->trans('InPost Pay options', [], 'Modules.Inpostizi.Promo'),
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'inpostizi_cart_rule_options';
    }
}
