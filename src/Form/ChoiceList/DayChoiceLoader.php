<?php

declare(strict_types=1);

namespace izi\prestashop\Form\ChoiceList;

use izi\prestashop\Configuration\DTO\Day;
use izi\prestashop\Configuration\DTO\Hour;
use izi\prestashop\Configuration\Factory\DayFactory;
use izi\prestashop\Configuration\Factory\DayFactoryInterface;
use Symfony\Component\Form\ChoiceList\ArrayChoiceList;
use Symfony\Component\Form\ChoiceList\ChoiceListInterface;
use Symfony\Component\Form\ChoiceList\Loader\ChoiceLoaderInterface;

final class DayChoiceLoader implements ChoiceLoaderInterface
{
    private const TRANSLATION_SOURCE = 'daychoiceloader';

    private $choices;

    private $dayFactory;

    public function __construct(DayFactoryInterface $dayFactory)
    {
        $this->dayFactory = $dayFactory;
    }

    /**
     * {@inheritDoc}
     */
    public function loadChoiceList($value = null): ChoiceListInterface
    {
        return new ArrayChoiceList($this->getChoices(), $value);
    }

    /**
     * {@inheritDoc}
     */
    public function loadChoicesForValues(array $values, $value = null): array
    {
        return $this->loadChoiceList($value)->getChoicesForValues($values);
    }

    /**
     * {@inheritDoc}
     */
    public function loadValuesForChoices(array $choices, $value = null): array
    {
        $choices = array_map(function ($choice): ?Day {
            if ($choice instanceof Day) {
                return $choice;
            }

            return $this->getChoices()[$choice] ?? null;
        }, $choices);

        return $this->loadChoiceList($value)->getValuesForChoices($choices);
    }

    private function getChoices(): iterable
    {
        if (isset($this->choices)) {
            return $this->choices;
        }

        $this->choices = [];

        for ($i = Day::MIN_DAY; $i < Day::MAX_DAY; ++$i) {
            $this->choices[$i] = $this->dayFactory->create($i);
        }

        return $this->choices;
    }
}
