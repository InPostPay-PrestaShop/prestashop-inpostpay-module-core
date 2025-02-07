<?php

declare(strict_types=1);

namespace izi\prestashop\View\Widget;

use izi\prestashop\Enum\StringEnum;

/**
 * @method static self Left()
 * @method static self Center()
 * @method static self Right()
 *
 * @deprecated
 */
final class Alignment extends StringEnum
{
    private const LEFT = 'left';
    private const CENTER = 'center';
    private const RIGHT = 'right';

    /**
     * @deprecated
     */
    public function getHtmlClass(): string
    {
        return sprintf('float-%s', $this->value);
    }

    public function toJustifyContentHtmlStyleValue(): string
    {
        switch ($this) {
            case self::Left():
                return 'start';
            case self::Center():
                return 'center';
            case self::Right():
                return 'end';
            default:
                throw new \LogicException('Unreachable statement.');
        }
    }
}
