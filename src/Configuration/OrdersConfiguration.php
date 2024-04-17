<?php

declare(strict_types=1);

namespace izi\prestashop\Configuration;

/**
 * @implements PersistentConfigurationInterface<OrdersConfigurationInterface>
 */
final class OrdersConfiguration implements OrdersConfigurationInterface, PersistentConfigurationInterface
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

    public function getInitialStatusId(?int $shopId = null): ?int
    {
        return (int) $this->configuration->get(self::INITIAL_OS_ID, $shopId);
    }

    public function getPaidStatusId(?int $shopId = null): ?int
    {
        return (int) $this->configuration->get(self::PAID_OS_ID, $shopId);
    }

    public function getStatusDescriptionMap(): array
    {
        return array_map([$this, 'decodeStatusDescriptionMap'], $this->configuration->getLocalized(self::STATUS_DESCRIPTION_MAP));
    }

    public function getStatusDescription(int $statusId, int $languageId, int $shopId): ?string
    {
        $map = $this->getStatusDescriptionMapping($languageId, $shopId);

        return $map[$statusId] ?? null;
    }

    public function isCarrierPaymentEnabled(?int $shopId = null): bool
    {
        return (bool) $this->configuration->get(self::ENABLE_CARRIER_PAYMENT, $shopId);
    }

    public function isBankPaymentEnabled(?int $shopId = null): bool
    {
        return (bool) $this->configuration->get(self::ENABLE_BANK_PAYMENT, $shopId);
    }

    public function getPointOfSaleId(?int $shopId = null): ?string
    {
        return $this->configuration->get(self::POS_ID, $shopId);
    }

    public function copy(): OrdersConfigurationInterface
    {
        return new DTO\OrdersConfiguration(
            $this->getInitialStatusId(),
            $this->getPaidStatusId(),
            $this->isBankPaymentEnabled(),
            $this->isCarrierPaymentEnabled(),
            $this->getPointOfSaleId(),
            $this->getStatusDescriptionMap()
        );
    }

    public function persist(OrdersConfigurationInterface $configuration): void
    {
        $this->configuration->set(self::INITIAL_OS_ID, $configuration->getInitialStatusId());
        $this->configuration->set(self::PAID_OS_ID, $configuration->getPaidStatusId());
        $this->configuration->set(self::ENABLE_BANK_PAYMENT, $configuration->isBankPaymentEnabled());
        $this->configuration->set(self::ENABLE_CARRIER_PAYMENT, $configuration->isCarrierPaymentEnabled());
        $this->configuration->set(self::POS_ID, $configuration->getPointOfSaleId());
        $this->setOrderStatusDescriptionMapping($configuration->getStatusDescriptionMap());
    }

    private function loadStatusDescriptionMap(int $languageId, ?int $shopId): array
    {
        $config = $this->configuration->get(self::STATUS_DESCRIPTION_MAP, $shopId, $languageId);

        return $this->decodeStatusDescriptionMap($config);
    }

    private function decodeStatusDescriptionMap($value): array
    {
        if (null === $value) {
            return [];
        }

        $map = json_decode($value, true);

        return is_array($map) ? $map : [];
    }

    private function getStatusDescriptionMapping(int $languageId, ?int $shopId = null): array
    {
        if (!isset($this->descriptionMappings[$languageId][(int) $shopId])) {
            $this->descriptionMappings[$languageId][(int) $shopId] = $this->loadStatusDescriptionMap($languageId, $shopId);
        }

        return $this->descriptionMappings[$languageId][(int) $shopId];
    }

    private function setOrderStatusDescriptionMapping(array $data): void
    {
        $data = array_map('array_filter', $data);

        $this->configuration->set(self::STATUS_DESCRIPTION_MAP, array_map('json_encode', $data));

        $this->descriptionMappings = [];
        foreach ($data as $languageId => $map) {
            $this->descriptionMappings[$languageId][0] = $map;
        }
    }
}
