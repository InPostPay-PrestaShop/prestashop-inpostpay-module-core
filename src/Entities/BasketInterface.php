<?php

declare(strict_types=1);

namespace izi\prestashop\Entities;

/**
 * @template T of object
 */
interface BasketInterface
{
    /**
     * @return int|string
     */
    public function getId();

    /**
     * @return T a native basket object
     */
    public function getEntity();
}
