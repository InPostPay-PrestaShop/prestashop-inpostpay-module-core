<?php

declare(strict_types=1);

namespace izi\prestashop\Hook\Common;

use izi\prestashop\Hook\HookInterface;

final class ActionCartSave implements HookInterface
{
    public const HOOK_NAME = 'actionCartSave';

    public static function getHookName(): string
    {
        return self::HOOK_NAME;
    }

    /**
     * @param array{cart?: \Cart} $parameters
     */
    public function execute(array $parameters): void
    {
        @trigger_error(sprintf('Hook "%s" is deprecated, use "%s" instead.', __CLASS__, ActionCartUpdateAfter::class), E_USER_DEPRECATED);
    }
}
