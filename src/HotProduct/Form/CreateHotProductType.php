<?php

declare(strict_types=1);

namespace izi\prestashop\HotProduct\Form;

use izi\prestashop\Form\Type\ObjectModelAutocompleteType;
use izi\prestashop\Form\Type\Product\CombinationByAttributesChoiceType;
use izi\prestashop\HotProduct\Message\CreateHotProductCommand;
use izi\prestashop\Translation\LegacyTranslator;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class CreateHotProductType extends AbstractType
{
    private const TRANSLATION_SOURCE = 'createhotproducttype';

    /**
     * @var \Context
     */
    private $context;

    /**
     * @var LegacyTranslator
     */
    private $translator;

    /**
     * @var UrlGeneratorInterface
     */
    private $urlGenerator;

    public function __construct(\Context $context, LegacyTranslator $translator, UrlGeneratorInterface $urlGenerator)
    {
        $this->context = $context;
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
            'label' => $this->context->getTranslator()->trans('Product', [], 'Admin.Global'),
            'placeholder' => $this->translator->l('Search products by name or reference...', self::TRANSLATION_SOURCE),
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
                'label' => $this->context->getTranslator()->trans('Combination', [], 'Admin.Global'),
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
