<?php

declare(strict_types=1);

namespace izi\prestashop\Configuration;

final class OrdersConfiguration implements OrdersConfigurationInterface
{
    private const INITIAL_OS_ID = 'INPOST_PAY_INITIAL_OS_ID';
    private const PAID_OS_ID = 'INPOST_PAY_authorized_payment';
    private const STATUS_DESCRIPTION_MAP = 'INPOST_PAY_OS_DESCRIPTION_MAP';
    private const ENABLE_CARRIER_PAYMENT = 'INPOST_PAY_payment_inpost';
    private const ENABLE_BANK_PAYMENT = 'INPOST_PAY_payment_aion';
    private const POS_ID = 'INPOST_PAY_pos_id';

    /**
     * @var LanguageAwareConfigurationInterface
     */
    private $configuration;

    private $descriptionMappings = [];

    public function __construct(LanguageAwareConfigurationInterface $configuration)
    {
        $this->configuration = $configuration;
    }

    public function getInitialStatusId(int $shopId = null): ?int
    {
        return (int) $this->configuration->get(self::INITIAL_OS_ID, $shopId);
    }

    public function getPaidStatusId(int $shopId = null): ?int
    {
        return (int) $this->configuration->get(self::PAID_OS_ID, $shopId);
    }

    public function getStatusDescriptionMapping(int $languageId, int $shopId = null): array
    {
        if (!isset($this->descriptionMappings[$languageId][(int) $shopId])) {
            $this->descriptionMappings[$languageId][(int) $shopId] = $this->loadOrderStatusDescriptionMap($languageId, $shopId);
        }

        return $this->descriptionMappings[$languageId][(int) $shopId];
    }

    public function getStatusDescription(int $statusId, int $languageId, int $shopId): ?string
    {
        $map = $this->getStatusDescriptionMapping($languageId, $shopId);

        return $map[$statusId] ?? null;
    }

    public function isCarrierPaymentEnabled(int $shopId = null): bool
    {
        return (bool) $this->configuration->get(self::ENABLE_CARRIER_PAYMENT, $shopId);
    }

    public function isBankPaymentEnabled(int $shopId = null): bool
    {
        return (bool) $this->configuration->get(self::ENABLE_BANK_PAYMENT, $shopId);
    }

    public function getPointOfSaleId(int $shopId = null): ?string
    {
        return $this->configuration->get(self::POS_ID, $shopId);
    }

    public function copy(): OrdersConfigurationInterface
    {
        return new DTO\OrdersConfiguration(
            $this->getInitialStatusId(),
            $this->getPaidStatusId(),
            [],
            $this->isBankPaymentEnabled(),
            $this->isCarrierPaymentEnabled(),
            $this->getPointOfSaleId()
        );
    }

    public function persist(OrdersConfigurationInterface $configuration): void
    {

    }

    private function loadOrderStatusDescriptionMap(int $languageId, ?int $shopId): array
    {
        $config = $this->configuration->get(self::STATUS_DESCRIPTION_MAP, $shopId, $languageId);

        if (null === $config) {
            return [];
        }

        $map = json_decode($config, true);

        return is_array($map) ? $map : [];
    }

    private function setOrderStatusDescriptionMapping(array $data): void
    {
        $data = array_filter($data);

        $this->configuration->set(self::STATUS_DESCRIPTION_MAP, json_encode($data));

        $this->descriptionMappings = [];
        foreach ($data as $languageId => $map) {
            $this->descriptionMappings[$languageId][0] = $map;
        }
    }
}
