<?php

declare(strict_types=1);

namespace izi\prestashop\Serializer;

interface DenormalizableInterface
{
    /**
     * @return static
     */
    public static function denormalize(array $data);
}
