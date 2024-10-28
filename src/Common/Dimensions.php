<?php

declare(strict_types=1);

namespace izi\prestashop\Common;

final class Dimensions
{
    /**
     * @var float $width
     */
    private $width;

    /**
     * @var float $height
     */
    private $height;

    /**
     * @var float $depth
     */
    private $depth;

    public function __construct(float $width, float $height, float $depth)
    {
        $this->width = $width;
        $this->height = $height;
        $this->depth = $depth;
    }

    public function getWidth(): float
    {
        return $this->width;
    }

    public function getHeight(): float
    {
        return $this->height;
    }

    public function getDepth(): float
    {
        return $this->depth;
    }
}
