<?php

declare(strict_types=1);

namespace izi\prestashop\ObjectModel;

/**
 * @template T of \ObjectModel
 *
 * @param class-string<T> $class
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
     * @param class-string<T> $class
     */
    public function __construct(ObjectManagerInterface $manager, string $class)
    {
        $this->manager = $manager;
        $this->class = $class;
    }

    /**
     * @return Query<T>
     */
    public function build(): Query
    {
        return new Query($this->manager, $this->class, parent::build());
    }
}
