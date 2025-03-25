<?php

declare(strict_types=1);

namespace izi\prestashop\ObjectModel;

/**
 * @template T of \ObjectModel
 *
 * @experimental
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
     * @var int|null
     */
    private $languageId;

    /**
     * @param class-string<T> $class
     */
    public function __construct(ObjectManagerInterface $manager, string $class, string $sql, ?int $languageId = null)
    {
        $this->manager = $manager;
        $this->class = $class;
        $this->sql = $sql;
        $this->languageId = $languageId;
    }

    public function getSql(): string
    {
        return $this->sql;
    }

    /**
     * @return T[]
     */
    public function getResult(): array
    {
        $data = $this->getArrayResult();

        return $this->manager->getHydrator()->hydrateCollection($data, $this->class, $this->languageId);
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
        if ([] === $data = $this->getArrayResult()) {
            return null;
        }

        return $this->manager->getHydrator()->hydrate($data, $this->class, null, $this->languageId);
    }

    /**
     * @return mixed
     */
    public function getSingleScalarResult()
    {
        $result = $this->manager->getConnection()->fetchFirstColumn($this->sql);

        if (count($result) > 1) {
            throw new \DomainException('More than one result was found for query although one row or none was expected.');
        }

        return array_shift($result);
    }
}
