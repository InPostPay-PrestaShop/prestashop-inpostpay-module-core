<?php

declare(strict_types=1);

namespace izi\prestashop\Form\Type;

use izi\prestashop\Form\ChoiceList\LazyChoiceLoader;
use izi\prestashop\Translation\LegacyTranslator;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\ChoiceList\Loader\ChoiceLoaderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @experimental
 */
final class ObjectModelAutocompleteType extends AbstractType
{
    private const TRANSLATION_SOURCE = 'objectmodelautocompletetype';

    /**
     * @var LegacyTranslator
     */
    private $translator;

    public function __construct(LegacyTranslator $translator)
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
                'loading_more_text' => $this->translator->l('Loading more results...', self::TRANSLATION_SOURCE),
                'no_results_found_text' => $this->translator->l('No results found', self::TRANSLATION_SOURCE),
                'no_more_results_text' => $this->translator->l('No more results', self::TRANSLATION_SOURCE),
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
