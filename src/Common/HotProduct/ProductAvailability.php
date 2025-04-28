<?php

declare(strict_types=1);

namespace izi\prestashop\Common\HotProduct;

final class ProductAvailability implements \JsonSerializable
{
    /**
     * @var \DateTimeImmutable|null
     */
    private $start_date;

    /**
     * @var \DateTimeImmutable|null
     */
    private $end_date;

    public function __construct(?\DateTimeImmutable $start_date = null, ?\DateTimeImmutable $end_date = null)
    {
        $this->start_date = $start_date;
        $this->end_date = $end_date;
    }

    public function getStartDate(): ?\DateTimeImmutable
    {
        return $this->start_date;
    }

    public function getEndDate(): ?\DateTimeImmutable
    {
        return $this->end_date;
    }

    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
