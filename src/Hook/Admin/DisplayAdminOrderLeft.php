<?php

declare(strict_types=1);

namespace izi\prestashop\Hook\Admin;

use izi\prestashop\Common\Delivery\DeliveryType;
use izi\prestashop\Hook\PrestaShopVersionAwareHookInterface;
use izi\prestashop\Hook\VersionRange;
use izi\prestashop\Repository\OrderDataRepositoryInterface;
use izi\prestashop\View\Templating\RendererInterface;

final class DisplayAdminOrderLeft implements PrestaShopVersionAwareHookInterface
{
    public const HOOK_NAME = 'displayAdminOrderLeft';

    /**
     * @var RendererInterface
     */
    private $renderer;

    /**
     * @var OrderDataRepositoryInterface
     */
    private $repository;

    public function __construct(RendererInterface $renderer, OrderDataRepositoryInterface $repository)
    {
        $this->renderer = $renderer;
        $this->repository = $repository;
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
            throw new \InvalidArgumentException(sprintf('Expected parameter "id_order" to be an integer, "%s" given.', get_debug_type($orderId)));
        }

        if (null === $data = $this->repository->getOrderData((string) $orderId)) {
            return '';
        }

        $isApmDelivery = DeliveryType::Apm() === $data->getDelivery()->getType();

        return $this->renderer->render('module:inpostizi/views/templates/hook/legacy/admin/order_details.tpl', [
            'delivery' => $isApmDelivery ? 'Paczkomat' : 'Kurier',
            'apm' => $isApmDelivery ? $data->getDelivery()->getPoint() : '',
        ]);
    }
}
