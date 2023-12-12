<?php

namespace izi\item;

abstract class Quantity extends \izi\Item
{
    public const DECIMAL = 'DECIMAL';
    public const INTEGER = 'INTEGER';

    /**
     * @var int|float
     */
    protected $quantity;

    /**
     * @var self::DECIMAL|self::integer|null
     */
    protected $quantity_type;

    /**
     * @var string|null
     */
    protected $quantity_unit;
}
