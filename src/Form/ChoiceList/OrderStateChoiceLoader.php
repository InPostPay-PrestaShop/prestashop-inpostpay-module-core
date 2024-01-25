<?php

declare(strict_types=1);

namespace izi\prestashop\Form\ChoiceList;

use izi\prestashop\ObjectModel\Repository\ObjectRepositoryInterface;
use Symfony\Component\Form\ChoiceList\ArrayChoiceList;
use Symfony\Component\Form\ChoiceList\ChoiceListInterface;
use Symfony\Component\Form\ChoiceList\Loader\ChoiceLoaderInterface;

final class OrderStateChoiceLoader implements ChoiceLoaderInterface
{
    private $context;

    /**
     * @var ObjectRepositoryInterface
     */
    private $repository;

    private $choices;

    /**
     * @param ObjectRepositoryInterface<\OrderState> $repository
     */
    public function __construct(\Context $context, ObjectRepositoryInterface $repository)
    {
        $this->context = $context;
        $this->repository = $repository;
    }

    public function loadChoiceList($value = null): ChoiceListInterface
    {
        return new ArrayChoiceList($this->getChoices(), $value);
    }

    public function loadChoicesForValues(array $values, $value = null): array
    {
        return $this->loadChoiceList($value)->getChoicesForValues($values);
    }

    public function loadValuesForChoices(array $choices, $value = null): array
    {
        return $this->loadChoiceList($value)->getValuesForChoices($choices);
    }

    private function getChoices(): iterable
    {
        if (isset($this->choices)) {
            return $this->choices;
        }

        $this->choices = [];

        foreach ($this->repository->findAll((int) $this->context->language->id) as $orderState) {
            $this->choices[$orderState->name] = (int) $orderState->id;
        }

        return $this->choices;
    }
}
