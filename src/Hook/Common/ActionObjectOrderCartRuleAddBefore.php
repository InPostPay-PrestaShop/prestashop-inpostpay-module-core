<?php

declare(strict_types=1);

namespace izi\prestashop\Hook\Common;

use izi\prestashop\Event\EventDispatcherInterface;
use izi\prestashop\Event\OrderCartRuleEvent;
use izi\prestashop\Hook\Exception\InvalidHookParamException;
use izi\prestashop\Hook\PrestaShopVersionAwareHookInterface;
use izi\prestashop\Hook\VersionRange;
use Symfony\Component\HttpFoundation\Request;

final class ActionObjectOrderCartRuleAddBefore implements PrestaShopVersionAwareHookInterface
{
    public const HOOK_NAME = 'actionObjectOrderCartRuleAddBefore';

    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    public function __construct(EventDispatcherInterface $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    public static function getHookName(): string
    {
        return self::HOOK_NAME;
    }

    public static function getVersionRange(): VersionRange
    {
        return new VersionRange(null, '9.0.1');
    }

    /**
     * @param array{object: \OrderCartRule, request: Request} $parameters
     */
    public function execute(array $parameters): void
    {
        $rule = $parameters['object'] ?? null;

        if (!$rule instanceof \OrderCartRule) {
            throw InvalidHookParamException::unexpectedType('object', $rule, \OrderCartRule::class);
        }

        $request = $parameters['request'] ?? null;

        if (!$request instanceof Request || 'inpost_izi_merchant_api_order_create' !== $request->attributes->get('_inpost_izi_route')) {
            return;
        }

        $this->dispatcher->dispatch(new OrderCartRuleEvent($rule), OrderCartRuleEvent::PRE_PERSIST);
    }
}
