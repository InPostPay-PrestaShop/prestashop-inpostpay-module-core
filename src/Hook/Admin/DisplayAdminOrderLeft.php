<?php

declare(strict_types=1);

namespace izi\prestashop\Hook\Admin;

use izi\prestashop\CartSession;
use izi\prestashop\Hook\PrestaShopVersionAwareHookInterface;
use izi\prestashop\Hook\VersionRange;
use izi\prestashop\View\Templating\RendererInterface;

final class DisplayAdminOrderLeft implements PrestaShopVersionAwareHookInterface
{
    public const HOOK_NAME = 'displayAdminOrderLeft';

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

    public static function getVersionRange(): VersionRange
    {
        return new VersionRange(null, '1.7.7');
    }

    /**
     * @param array{id_order?: int} $parameters
     */
    public function execute(array $parameters): string
    {
        $orderId = $parameters['id_order'] ?? null;

        if (!is_int($orderId)) {
            throw new \InvalidArgumentException(sprintf('Parameter "id_order" expected to be an integer, "%s" given.', get_debug_type($orderId)));
        }

        if (!$orderData = CartSession::getOrderData($orderId)) {
            return '';
        }

        $orderData = json_decode($orderData, false);

        return $this->renderer->render('module:inpostizi/views/templates/hook/admin_order_left.tpl', [
            'delivery' => 'APM' === $orderData->delivery->delivery_type ? 'Paczkomat' : 'Kurier',
            'apm' => 'APM' === $orderData->delivery->delivery_type ? $orderData->delivery->delivery_point : '',
        ]);
    }
}
