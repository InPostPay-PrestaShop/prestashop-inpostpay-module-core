<?php

declare(strict_types=1);

namespace izi\prestashop\Form\ChoiceList;

use izi\prestashop\ObjectModel\Repository\ObjectRepositoryInterface;
use Symfony\Component\Form\ChoiceList\ArrayChoiceList;
use Symfony\Component\Form\ChoiceList\ChoiceListInterface;
use Symfony\Component\Form\ChoiceList\Loader\ChoiceLoaderInterface;

final class OrderStateChoiceLoader implements ChoiceLoaderInterface
{
    /**
     * @var ObjectRepositoryInterface
     */
    private $repository;

    private $choices;

    /**
     * @param ObjectRepositoryInterface<\OrderState> $repository
     */
    public function __construct(ObjectRepositoryInterface $repository)
    {
        $this->repository = $repository;
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
        $choices = array_map(function ($choice): ?\OrderState {
            if ($choice instanceof \OrderState) {
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

        foreach ($this->repository->findAll() as $orderState) {
            $this->choices[$orderState->id] = $orderState;
        }

        return $this->choices;
    }
}
