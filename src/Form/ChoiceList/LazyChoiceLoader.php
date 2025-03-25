<?php

declare(strict_types=1);

namespace izi\prestashop\Form\ChoiceList;

use Symfony\Component\Form\ChoiceList\ArrayChoiceList;
use Symfony\Component\Form\ChoiceList\ChoiceListInterface;
use Symfony\Component\Form\ChoiceList\Loader\ChoiceLoaderInterface;

/**
 * @author Yonel Ceruto <yonelceruto@gmail.com>
 *
 * @see \Symfony\Component\Form\ChoiceList\Loader\LazyChoiceLoader
 */
final class LazyChoiceLoader implements ChoiceLoaderInterface
{
    /**
     * @var ChoiceLoaderInterface
     */
    private $loader;

    /**
     * @var ChoiceListInterface|null
     */
    private $choiceList;

    public function __construct(ChoiceLoaderInterface $loader)
    {
        $this->loader = $loader;
    }

    /**
     * @param callable|null $value
     */
    public function loadChoiceList($value = null): ChoiceListInterface
    {
        return $this->choiceList ?? $this->choiceList = new ArrayChoiceList([], $value);
    }

    /**
     * @param callable|null $value
     */
    public function loadChoicesForValues(array $values, $value = null): array
    {
        $choices = $this->loader->loadChoicesForValues($values, $value);
        $this->choiceList = new ArrayChoiceList($choices, $value);

        return $choices;
    }

    /**
     * @param callable|null $value
     */
    public function loadValuesForChoices(array $choices, $value = null): array
    {
        $values = $this->loader->loadValuesForChoices($choices, $value);

        if (null === $this->choiceList || $this->choiceList->getValuesForChoices($choices) !== $values) {
            $this->loadChoicesForValues($values, $value);
        }

        return $values;
    }
}
