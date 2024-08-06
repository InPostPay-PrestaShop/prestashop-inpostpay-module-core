<?php

declare(strict_types=1);

namespace izi\prestashop\Form\ChoiceList;

use izi\prestashop\ObjectModel\ObjectManagerInterface;
use izi\prestashop\ObjectModel\OrderMaintainingLoaderTrait;
use izi\prestashop\ObjectModel\QueryBuilder;
use Symfony\Component\Form\ChoiceList\ArrayChoiceList;
use Symfony\Component\Form\ChoiceList\ChoiceListInterface;
use Symfony\Component\Form\ChoiceList\Loader\ChoiceLoaderInterface;

/**
 * @template T of \ObjectModel
 */
final class ObjectModelChoiceLoader implements ChoiceLoaderInterface
{
    use OrderMaintainingLoaderTrait;

    /**
     * @var class-string<T>
     */
    private $class;

    /**
     * @var QueryBuilder<T>|null
     */
    private $queryBuilder;

    /**
     * @var int|null
     */
    private $languageId;

    /**
     * @var int|null
     */
    private $shopId;

    /**
     * @var T[]
     */
    private $choices;

    /**
     * @param class-string<T> $class
     * @param QueryBuilder<T>|null $queryBuilder
     */
    public function __construct(ObjectManagerInterface $manager, string $class, ?QueryBuilder $queryBuilder = null, ?int $languageId = null, ?int $shopId = null)
    {
        $this->manager = $manager;
        $this->class = $class;
        $this->queryBuilder = $queryBuilder;
        $this->languageId = $languageId;
        $this->shopId = $shopId;
    }

    public function loadChoiceList($value = null): ChoiceListInterface
    {
        if (!isset($this->choices)) {
            $this->choices = $this->getChoices();
        }

        return new ArrayChoiceList($this->choices, $value);
    }

    public function loadValuesForChoices(array $choices, $value = null): array
    {
        if ([] === $choices) {
            return [];
        }

        if (null !== $value || isset($this->choices)) {
            return $this->loadChoiceList($value)->getValuesForChoices($choices);
        }

        $values = [];

        foreach ($choices as $i => $choice) {
            if ($choice instanceof $this->class) {
                $values[$i] = (string) $choice->id;
            } elseif (is_int($choice)) {
                $values[$i] = (string) $choice;
            }
        }

        return $values;
    }

    public function loadChoicesForValues(array $values, $value = null): array
    {
        if ([] === $values) {
            return [];
        }

        if (null !== $value || isset($this->choices) || null !== $this->queryBuilder) {
            return $this->loadChoiceList($value)->getChoicesForValues($values);
        }

        return $this->findByIdsMaintainingOrder(
            $this->class,
            $values,
            $this->languageId,
            $this->shopId
        );
    }

    private function getChoices(): array
    {
        return $this->queryBuilder
            ? $this->queryBuilder
                ->setLanguageId($this->languageId)
                ->build()
                ->getResult()
            : $this->manager
                ->getRepository($this->class)
                ->findAll($this->languageId, $this->shopId);
    }
}
