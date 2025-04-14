<?php

declare(strict_types=1);

namespace izi\prestashop\ObjectModel;

/**
 * @template T of \ObjectModel
 *
 * @param class-string<T> $class
 *
 * @experimental API may change, extending {@see \DbQuery} is a temporary hack
 */
class QueryBuilder extends \DbQuery
{
    /**
     * @var ObjectManagerInterface
     */
    private $manager;

    /**
     * @var class-string<T>
     */
    private $class;

    /**
     * @var int|null
     */
    private $languageId;

    /**
     * @param class-string<T> $class
     */
    public function __construct(ObjectManagerInterface $manager, string $class, ?int $languageId = null)
    {
        $this->manager = $manager;
        $this->class = $class;
        $this->languageId = $languageId;
    }

    /**
     * @return $this
     */
    public function setLanguageId(?int $languageId): self
    {
        $this->languageId = $languageId;

        return $this;
    }

    /**
     * @return Query<T>
     */
    public function build(): Query
    {
        return new Query($this->manager, $this->class, parent::build(), $this->languageId);
    }
}
