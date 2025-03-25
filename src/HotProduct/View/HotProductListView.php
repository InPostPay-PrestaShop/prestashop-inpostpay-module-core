<?php

declare(strict_types=1);

namespace izi\prestashop\HotProduct\View;

/**
 * @implements \IteratorAggregate<int, HotProductView>
 */
final class HotProductListView implements \IteratorAggregate
{
    /**
     * @var HotProductView[]
     */
    private $products;

    /**
     * @var bool
     */
    private $statusAvailable;

    /**
     * @param HotProductView[] $products
     */
    public function __construct(array $products, bool $statusAvailable)
    {
        $this->products = $products;
        $this->statusAvailable = $statusAvailable;
    }

    /**
     * @return \Iterator<int, HotProductView>
     */
    public function getIterator(): \Iterator
    {
        return new \ArrayIterator($this->products);
    }

    /**
     * @return HotProductView[]
     */
    public function getProducts(): array
    {
        return $this->products;
    }

    public function isStatusAvailable(): bool
    {
        return $this->statusAvailable;
    }
}
