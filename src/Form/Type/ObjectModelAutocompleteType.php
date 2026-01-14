<?php

declare(strict_types=1);

namespace izi\prestashop\Form\Type;

use izi\prestashop\Form\ChoiceList\LazyChoiceLoader;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\ChoiceList\Loader\ChoiceLoaderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @experimental
 */
final class ObjectModelAutocompleteType extends AbstractType
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function getParent(): string
    {
        return ObjectModelType::class;
    }

    public function finishView(FormView $view, FormInterface $form, array $options): void
    {
        $attr = $view->vars['attr'] ?? [];

        $values = [];
        $values['url'] = $options['autocomplete_url'];

        if ($options['min_characters']) {
            $values['min-characters'] = $options['min_characters'];
        }

        $values['loading-more-text'] = $options['loading_more_text'];
        $values['no-results-found-text'] = $options['no_results_found_text'];
        $values['no-more-results-text'] = $options['no_more_results_text'];

        foreach ($values as $name => $value) {
            $attr['data-' . $name] = $value;
        }

        $view->vars['uses_autocomplete'] = true;
        $view->vars['attr'] = $attr;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setRequired([
                'autocomplete_url',
            ])
            ->setDefaults([
                'loading_more_text' => $this->translator->trans('Loading more results...', [], 'Modules.Inpostizi.Config'),
                'no_results_found_text' => $this->translator->trans('No results found', [], 'Modules.Inpostizi.Config'),
                'no_more_results_text' => $this->translator->trans('No more results', [], 'Modules.Inpostizi.Config'),
                'min_characters' => null,
                'choice_loader' => static function (Options $options, ?ChoiceLoaderInterface $loader): ?ChoiceLoaderInterface {
                    if (null === $loader) {
                        return null;
                    }

                    return new LazyChoiceLoader($loader);
                },
            ])
            ->setAllowedTypes('autocomplete_url', 'string');
    }
}
