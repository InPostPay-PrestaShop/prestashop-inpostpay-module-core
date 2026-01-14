<?php

declare(strict_types=1);

namespace izi\prestashop\Webservice\Model;

use izi\prestashop\ObjectModel\ObjectManagerInterface;
use izi\prestashop\Repository\OrderDataRepositoryInterface;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;

/**
 * @internal
 *
 * @mixin InPostOrder
 */
trait InPostOrderTrait
{
    public function getWebserviceParameters($ws_params_attribute_name = null): array
    {
        $this->webserviceParameters['fields']['inpost_point'] = [
            'getter' => 'getWsInPostPoint',
            'setter' => false,
        ];

        return parent::getWebserviceParameters($ws_params_attribute_name);
    }

    public function getWsInPostPoint(): ?string
    {
        if ('inpostizi' === $this->module) {
            return $this->getPointIdFromModuleData();
        }

        if (null !== $pointId = $this->getPointIdFromBaseLinkerData()) {
            return $pointId;
        }

        if (!\is_callable(['parent', 'getWsInPostPoint'])) {
            return null;
        }

        return parent::getWsInPostPoint();
    }

    private function getPointIdFromModuleData(): ?string
    {
        try {
            $data = $this->get(OrderDataRepositoryInterface::class)->getOrderData((string) $this->id);
        } catch (ServiceNotFoundException $e) {
            return null;
        }

        if (null === $data) {
            return null;
        }

        return $data->getDelivery()->getPoint();
    }

    private function getPointIdFromBaseLinkerData(): ?string
    {
        if (!$this instanceof \BaseLinkerOrder) {
            return null;
        }

        if (!$this->bl_delivery_point_id) {
            return null;
        }

        if (0 >= $carrierId = (int) $this->id_carrier) {
            return null;
        }

        try {
            $carrier = $this->get(ObjectManagerInterface::class)->find(\Carrier::class, (int) $carrierId);
        } catch (ServiceNotFoundException $e) {
            return null;
        }

        if (null === $carrier) {
            return null;
        }

        if (!preg_match('/paczkomat|inpost/i', $carrier->name)) {
            return null;
        }

        return $this->bl_delivery_point_id;
    }

    /**
     * @template T
     *
     * @param class-string<T> $id
     *
     * @return T
     */
    private function get(string $id)
    {
        return \InPostIzi::getInstance()->get($id);
    }
}
