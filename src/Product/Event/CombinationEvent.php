<?php

declare(strict_types=1);

namespace izi\prestashop\Product\Event;

use izi\prestashop\Event\Event;

final class CombinationEvent extends Event
{
    /**
     * Dispatched before combination is deleted.
     */
    public const DELETION = 'inpostizi.combination.deletion';
    public const DELETED = 'inpostizi.combination.deleted';
    public const UPDATED = 'inpostizi.combination.updated';

    /**
     * @var \Combination
     */
    private $combination;

    public function __construct(\Combination $combination)
    {
        $this->combination = $combination;
    }

    public function getCombination(): \Combination
    {
        return $this->combination;
    }
}
