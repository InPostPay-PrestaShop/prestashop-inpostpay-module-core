<?php

declare(strict_types=1);

namespace izi\prestashop\Configuration;

final class OrdersConfiguration implements OrdersConfigurationInterface
{
    private const PAID_OS_ID = 'INPOST_PAY_authorized_payment';
    private const STATUS_DESCRIPTION_MAP = 'INPOST_PAY_OS_DESCRIPTION_MAP';

    /**
     * @var LanguageAwareConfigurationInterface
     */
    private $configuration;

    private $descriptionMappings = [];

    public function __construct(LanguageAwareConfigurationInterface $configuration)
    {
        $this->configuration = $configuration;
    }

    public function getPaidOrderStatusId(int $shopId = null): int
    {
        return (int) $this->configuration->get(self::PAID_OS_ID, $shopId);
    }

    public function getOrderStatusDescriptionMapping(int $languageId, int $shopId = null): array
    {
        if (!isset($this->descriptionMappings[$languageId][(int) $shopId])) {
            $this->descriptionMappings[$languageId][(int) $shopId] = $this->loadOrderStatusDescriptionMap($languageId, $shopId);
        }

        return $this->descriptionMappings[$languageId][(int) $shopId];
    }

    public function getOrderStatusDescription(int $statusId, int $languageId, int $shopId): ?string
    {
        $map = $this->getOrderStatusDescriptionMapping($languageId, $shopId);

        return $map[$statusId] ?? null;
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
