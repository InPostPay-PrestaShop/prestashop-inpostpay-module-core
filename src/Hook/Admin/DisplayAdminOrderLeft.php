<?php

declare(strict_types=1);

namespace izi\prestashop\Hook\Admin;

use izi\prestashop\Common\Delivery\DeliveryType;
use izi\prestashop\Hook\Exception\InvalidHookParamException;
use izi\prestashop\Hook\PrestaShopVersionAwareHookInterface;
use izi\prestashop\Hook\VersionRange;
use izi\prestashop\Repository\OrderDataRepositoryInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

final class DisplayAdminOrderLeft implements PrestaShopVersionAwareHookInterface
{
    public const HOOK_NAME = 'displayAdminOrderLeft';

    /**
     * @var Environment
     */
    private $twig;

    /**
     * @var OrderDataRepositoryInterface
     */
    private $repository;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(Environment $twig, OrderDataRepositoryInterface $repository, TranslatorInterface $translator)
    {
        $this->twig = $twig;
        $this->repository = $repository;
        $this->translator = $translator;
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

        if (!\is_int($orderId)) {
            throw InvalidHookParamException::unexpectedType('id_order', $parameters['id_order'], 'int');
        }

        if (null === $data = $this->repository->getOrderData((string) $orderId)) {
            return '';
        }

        $deliveryType = $data->getDelivery()->getType();

        return $this->twig->render('@Modules/inpostizi/views/templates/hook/legacy/admin/order_details.html.twig', [
            'delivery' => $deliveryType->trans($this->translator),
            'apm' => DeliveryType::Apm() === $deliveryType ? (string) $data->getDelivery()->getPoint() : '',
            'issue_invoice' => null !== $data->getInvoiceDetails(),
        ]);
    }
}
