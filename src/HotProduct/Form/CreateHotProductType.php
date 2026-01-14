<?php

declare(strict_types=1);

namespace izi\prestashop\HotProduct\Form;

use izi\prestashop\Form\Type\ObjectModelAutocompleteType;
use izi\prestashop\Form\Type\Product\CombinationByAttributesChoiceType;
use izi\prestashop\HotProduct\Message\CreateHotProductCommand;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class CreateHotProductType extends AbstractType
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var UrlGeneratorInterface
     */
    private $urlGenerator;

    public function __construct(TranslatorInterface $translator, UrlGeneratorInterface $urlGenerator)
    {
        $this->translator = $translator;
        $this->urlGenerator = $urlGenerator;
    }

    public function getParent(): string
    {
        return UpdateHotProductType::class;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('productId', ObjectModelAutocompleteType::class, [
            'class' => \Product::class,
            'input' => 'id',
            'label' => $this->translator->trans('Product', [], 'Admin.Global'),
            'placeholder' => $this->translator->trans('Search products by name or reference...', [], 'Modules.Inpostizi.Hotproduct'),
            'autocomplete_url' => $this->urlGenerator->generate('admin_inpost_izi_products_autocomplete'),
            'choice_label' => static function (\Product $product): string {
                $label = $product->name ?? 'Product #' . $product->id;

                if ($product->reference) {
                    $label .= ' (ref. ' . $product->reference . ')';
                }

                return $label;
            },
        ]);

        $builder->get('productId')->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
            $product = $event->getForm()->getNormData();

            if (!$product instanceof \Product || !$product->cache_default_attribute) {
                return;
            }

            $event->getForm()->getParent()->add('combinationId', CombinationByAttributesChoiceType::class, [
                'label' => $this->translator->trans('Combination', [], 'Admin.Global'),
                'product_id' => (int) $product->id,
                'input' => 'id',
            ]);
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CreateHotProductCommand::class,
        ]);
    }
}
