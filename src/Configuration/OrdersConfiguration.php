<?php

declare(strict_types=1);

namespace izi\prestashop\Configuration;

use izi\prestashop\Common\PaymentType;
use izi\prestashop\Configuration\DTO\Order\MessageOptions;
use izi\prestashop\Serializer\SafeDeserializerTrait;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @implements PersistentConfigurationInterface<OrdersConfigurationInterface>
 */
final class OrdersConfiguration implements OrdersConfigurationInterface, PersistentConfigurationInterface
{
    use SafeDeserializerTrait;

    private const INITIAL_OS_ID = 'INPOST_PAY_INITIAL_OS_ID';
    private const COD_OS_ID = 'INPOST_PAY_COD_OS_ID';
    private const PAID_OS_ID = 'INPOST_PAY_authorized_payment';
    private const STATUS_DESCRIPTION_MAP = 'INPOST_PAY_OS_DESCRIPTION_MAP';
    private const AVAILABLE_PAYMENT_OPTIONS = 'INPOST_PAY_AVAILABLE_PAYMENT_OPTIONS';
    private const POS_ID = 'INPOST_PAY_pos_id';
    private const MESSAGE_FORMAT = 'INPOST_PAY_ORDER_MESSAGE_FORMAT';

    /**
     * @var LanguageAwareConfigurationInterface
     */
    private $configuration;

    private $descriptionMappings = [];

    private $availablePaymentOptions = [];

    /**
     * @var array<int, MessageOptions>
     */
    private $messageOptions = [];

    public function __construct(LanguageAwareConfigurationInterface $configuration, SerializerInterface $serializer)
    {
        $this->configuration = $configuration;
        $this->serializer = $serializer;
    }

    public function getInitialStatusId(?PaymentType $paymentType = null, ?int $shopId = null): int
    {
        if (PaymentType::CashOnDelivery() === $paymentType && null !== $codStatusId = $this->getCashOnDeliveryStatusId($shopId)) {
            return $codStatusId;
        }

        return (int) $this->getDefaultInitialStatusId($shopId);
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

    /**
     * @return PaymentType[]
     */
    public function getAvailablePaymentOptions(?int $shopId = null): array
    {
        if (!isset($this->availablePaymentOptions[(int) $shopId])) {
            $this->availablePaymentOptions[(int) $shopId] = $this->loadAvailablePaymentOptions($shopId);
        }

        return $this->availablePaymentOptions[(int) $shopId];
    }

    public function getPointOfSaleId(?int $shopId = null): ?string
    {
        return $this->configuration->get(self::POS_ID, $shopId);
    }

    public function getMessageFormat(?int $shopId = null): string
    {
        return $this->getMessageOptions($shopId)->getFormat();
    }

    public function copy(): OrdersConfigurationInterface
    {
        $configuration = new DTO\OrdersConfiguration(
            $this->getDefaultInitialStatusId(),
            $this->getPaidStatusId(),
            $this->getPointOfSaleId(),
            $this->getStatusDescriptionMap(),
            $this->getAvailablePaymentOptions()
        );

        return $configuration
            ->setCashOnDeliveryStatusId($this->getCashOnDeliveryStatus())
            ->setMessageOptions($this->getMessageOptions());
    }

    public function persist(OrdersConfigurationInterface $configuration): void
    {
        $defaultInitialStatusId = $configuration->getInitialStatusId();
        $codStatusId = $configuration->getInitialStatusId(PaymentType::CashOnDelivery());

        $this->configuration->set(self::INITIAL_OS_ID, $defaultInitialStatusId);
        $this->configuration->set(self::COD_OS_ID, $codStatusId);
        $this->configuration->set(self::PAID_OS_ID, $configuration->getPaidStatusId());
        $this->configuration->set(self::POS_ID, $configuration->getPointOfSaleId());
        $this->setOrderStatusDescriptionMapping($configuration->getStatusDescriptionMap());
        $this->setAvailablePaymentOptions($configuration);
        $this->setMessageOptions($configuration);
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

    private function loadAvailablePaymentOptions(?int $shopId): array
    {
        $config = $this->configuration->get(self::AVAILABLE_PAYMENT_OPTIONS, $shopId);

        return $this->decodeAvailablePaymentOptions($config);
    }

    private function decodeAvailablePaymentOptions($value): array
    {
        if (null === $value) {
            return PaymentType::getAvailableByDefaultPaymentOptions();
        }

        $data = json_decode($value, true);

        if (!is_array($data)) {
            return [];
        }

        return array_filter(array_map([PaymentType::class, 'tryFrom'], $data));
    }

    private function setAvailablePaymentOptions(OrdersConfigurationInterface $configuration): void
    {
        $availablePaymentOptions = array_values($configuration->getAvailablePaymentOptions());

        $this->configuration->set(self::AVAILABLE_PAYMENT_OPTIONS, json_encode($availablePaymentOptions));
        $this->availablePaymentOptions = [0 => $availablePaymentOptions];
    }

    private function getMessageOptions(?int $shopId = null): MessageOptions
    {
        if (!isset($this->messageOptions[(int) $shopId])) {
            $this->messageOptions[(int) $shopId] = $this->loadMessageOptions($shopId);
        }

        return $this->messageOptions[(int) $shopId];
    }

    private function loadMessageOptions(?int $shopId): MessageOptions
    {
        $config = $this->configuration->get(self::MESSAGE_FORMAT, $shopId);

        if (null !== $config && $options = $this->deserialize($config, MessageOptions::class)) {
            return $options;
        }

        return new MessageOptions();
    }

    private function setMessageOptions(OrdersConfigurationInterface $configuration): void
    {
        if ($configuration instanceof DTO\OrdersConfiguration) {
            $options = $configuration->getMessageOptions();
        } else {
            $options = new MessageOptions($configuration->getMessageFormat(), false, true);
        }

        $value = $this->serializer->serialize($options, 'json');
        $this->configuration->set(self::MESSAGE_FORMAT, $value);
        $this->messageOptions[0] = $options;
    }

    private function getDefaultInitialStatusId(?int $shopId = null): ?int
    {
        $value = $this->configuration->get(self::INITIAL_OS_ID, $shopId);

        return null === $value ? null : (int) $value;
    }

    private function getCashOnDeliveryStatusId(?int $shopId = null): ?int
    {
        $value = $this->configuration->get(self::COD_OS_ID, $shopId);

        return null === $value ? null : (int) $value;
    }

    private function getCashOnDeliveryStatus(): ?\OrderState
    {
        if (null === $statusId = $this->getCashOnDeliveryStatusId()) {
            return null;
        }

        $status = new \OrderState();
        $status->id = $statusId;

        return $status;
    }
}
