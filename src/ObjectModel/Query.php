<?php

declare(strict_types=1);

namespace izi\prestashop\ObjectModel;

/**
 * @template T of \ObjectModel
 */
class Query
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
     * @var string
     */
    private $sql;

    /**
     * @param class-string<T> $class
     */
    public function __construct(ObjectManagerInterface $manager, string $class, string $sql)
    {
        $this->manager = $manager;
        $this->class = $class;
        $this->sql = $sql;
    }

    /**
     * @return T[]
     */
    public function getResult(): array
    {
        $data = $this->getArrayResult();

        return $this->manager->getHydrator()->hydrateCollection($data, $this->class);
    }

    public function getArrayResult(): array
    {
        return $this->manager->getConnection()->fetchAllAssociative($this->sql);
    }

    /**
     * @return T|null
     */
    public function getOneOrNullResult(): ?\ObjectModel
    {
        $data = $this->getArrayResult();

        return $this->manager->getHydrator()->hydrate($data, $this->class);
    }
}
