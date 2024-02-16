<?php

declare(strict_types=1);

namespace izi\prestashop\Form\ChoiceList;

use izi\prestashop\Configuration\DTO\Hour;
use izi\prestashop\Configuration\Factory\HourFactoryInterface;
use Symfony\Component\Form\ChoiceList\ArrayChoiceList;
use Symfony\Component\Form\ChoiceList\ChoiceListInterface;
use Symfony\Component\Form\ChoiceList\Loader\ChoiceLoaderInterface;

final class HourChoiceLoader implements ChoiceLoaderInterface
{
    private $choices;

    private $hourFactory;

    public function __construct(HourFactoryInterface $hourFactory)
    {
        $this->hourFactory = $hourFactory;
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
        $choices = array_map(function ($choice): ?Hour {
            if ($choice instanceof Hour) {
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

        for ($i = Hour::MIN_HOUR; $i < Hour::MAX_HOUR; ++$i) {
            $this->choices[$i] = $this->hourFactory->create($i);
        }

        return $this->choices;
    }
}
