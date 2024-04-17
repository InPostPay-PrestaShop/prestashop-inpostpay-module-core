<?php

declare(strict_types=1);

namespace izi\prestashop\Configuration\DTO;

use izi\prestashop\Configuration\OrdersConfigurationInterface;
use Symfony\Component\Validator\Constraints as Assert;

final class OrdersConfiguration implements OrdersConfigurationInterface
{
    /**
     * @var int|null
     *
     * @Assert\NotNull()
     */
    private $initialStatusId;

    /**
     * @var int|null
     *
     * @Assert\NotNull()
     */
    private $paidStatusId;

    /**
     * @var array
     */
    private $statusDescriptionMap;

    /**
     * @var bool|null
     *
     * @Assert\NotNull()
     */
    private $bankPaymentEnabled;

    /**
     * @var bool|null
     *
     * @Assert\NotNull()
     */
    private $carrierPaymentEnabled;

    /**
     * @var string|null
     *
     * @Assert\NotBlank()
     */
    private $posId;

    public function __construct(?int $initialStatusId = null, ?int $paidStatusId = null, ?bool $bankPaymentEnabled = null, ?bool $carrierPaymentEnabled = null, ?string $posId = null, array $statusDescriptionMap = [])
    {
        $this->initialStatusId = $initialStatusId;
        $this->paidStatusId = $paidStatusId;
        $this->bankPaymentEnabled = $bankPaymentEnabled;
        $this->carrierPaymentEnabled = $carrierPaymentEnabled;
        $this->posId = $posId;
        $this->statusDescriptionMap = $statusDescriptionMap;
    }

    public function getInitialStatusId(?int $shopId = null): ?int
    {
        return $this->initialStatusId;
    }

    public function setInitialStatusId(?\OrderState $initialStatus): OrdersConfiguration
    {
        $this->initialStatusId = null === $initialStatus ? null : (int) $initialStatus->id;

        return $this;
    }

    public function getPaidStatusId(?int $shopId = null): ?int
    {
        return $this->paidStatusId;
    }

    public function setPaidStatusId(?\OrderState $paidStatus): OrdersConfiguration
    {
        $this->paidStatusId = null === $paidStatus ? null : (int) $paidStatus->id;

        return $this;
    }

    public function getStatusDescription(int $statusId, int $languageId, ?int $shopId = null): ?string
    {
        return $this->statusDescriptionMap[$languageId][$statusId] ?? null;
    }

    public function getStatusDescriptionMap(): array
    {
        return $this->statusDescriptionMap;
    }

    public function setStatusDescriptionMap(array $statusDescriptionMap): OrdersConfiguration
    {
        $this->statusDescriptionMap = $statusDescriptionMap;

        return $this;
    }

    public function isBankPaymentEnabled(?int $shopId = null): bool
    {
        return true === $this->bankPaymentEnabled;
    }

    public function setBankPaymentEnabled(?bool $bankPaymentEnabled): OrdersConfiguration
    {
        $this->bankPaymentEnabled = $bankPaymentEnabled;

        return $this;
    }

    public function isCarrierPaymentEnabled(?int $shopId = null): bool
    {
        return true === $this->carrierPaymentEnabled;
    }

    public function setCarrierPaymentEnabled(?bool $carrierPaymentEnabled): OrdersConfiguration
    {
        $this->carrierPaymentEnabled = $carrierPaymentEnabled;

        return $this;
    }

    public function getPointOfSaleId(?int $shopId = null): ?string
    {
        return $this->posId;
    }

    public function setPointOfSaleId(?string $posId): OrdersConfiguration
    {
        $this->posId = $posId;

        return $this;
    }
}
