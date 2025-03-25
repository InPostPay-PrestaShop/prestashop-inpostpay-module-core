<?php

declare(strict_types=1);

namespace izi\prestashop\BasketApp;

/**
 * @template T
 *
 * @template-implements \IteratorAggregate<int, T>
 */
final class PaginationPage implements \IteratorAggregate, \JsonSerializable
{
    /**
     * @var T[]
     */
    private $content;

    /**
     * @var int
     */
    private $total_items;

    /**
     * @var int
     */
    private $page_index;

    /**
     * @var int
     */
    private $page_size;

    /**
     * @param T[] $content
     */
    public function __construct(array $content, int $total_items, int $page_index, int $page_size)
    {
        $this->content = $content;
        $this->total_items = $total_items;
        $this->page_index = $page_index;
        $this->page_size = $page_size;
    }

    /**
     * @return T[]
     */
    public function getItems(): array
    {
        return $this->content;
    }

    public function getTotalCount(): int
    {
        return $this->total_items;
    }

    public function getPageIndex(): int
    {
        return $this->page_index;
    }

    public function getPageSize(): int
    {
        return $this->page_size;
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }

    /**
     * @return \Traversable<int, T>
     */
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->content);
    }
}
