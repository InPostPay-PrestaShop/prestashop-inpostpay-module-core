<?php

declare(strict_types=1);

namespace izi\prestashop\Hook\Admin;

use izi\prestashop\CartSession;
use izi\prestashop\Hook\HookInterface;
use izi\prestashop\View\Templating\RendererInterface;

final class DisplayAdminOrderSide implements HookInterface
{
    public const HOOK_NAME = 'displayAdminOrderSide';

    /**
     * @var RendererInterface
     */
    private $renderer;

    public function __construct(RendererInterface $renderer)
    {
        $this->renderer = $renderer;
    }

    public static function getHookName(): string
    {
        return self::HOOK_NAME;
    }

    /**
     * @param array{id_order: int} $parameters
     */
    public function execute(array $parameters): string
    {
        if (!$orderData = CartSession::getOrderData($parameters['id_order'])) {
            return '';
        }

        $orderData = json_decode($orderData, false);

        return $this->renderer->render('module:inpostizi/views/templates/hook/backend.tpl', [
            'delivery' => 'APM' === $orderData->delivery->delivery_type ? 'Paczkomat' : 'Kurier',
            'apm' => 'APM' === $orderData->delivery->delivery_type ? $orderData->delivery->delivery_point : '',
        ]);
    }
}
