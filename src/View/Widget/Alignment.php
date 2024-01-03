<?php

declare(strict_types=1);

namespace izi\prestashop\View\Widget;

use izi\prestashop\Enum\StringEnum;

/**
 * @method static self Left()
 * @method static self Center()
 * @method static self Right()
 */
final class Alignment extends StringEnum
{
    private const LEFT = 'left';
    private const CENTER = 'center';
    private const RIGHT = 'right';

    public function getHtmlClass(): string
    {
        return sprintf('float-%s', $this->value);
    }
}
