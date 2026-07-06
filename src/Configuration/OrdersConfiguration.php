<?php

declare(strict_types=1);

namespace izi\prestashop\Configuration;

use izi\prestashop\Common\PaymentType;
use izi\prestashop\Configuration\DTO\Order\MessageOptions;
use izi\prestashop\Serializer\SafeDeserializerTrait;
use Psr\Log\LoggerInterface;
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
    private const POS_ID = 'INPOST_PAY_pos_id';
    private const MESSAGE_FORMAT = 'INPOST_PAY_ORDER_MESSAGE_FORMAT';

    /**
     * @var LanguageAwareConfigurationInterface
     */
    private $configuration;

    private $descriptionMappings = [];

    /**
     * @var array<int, MessageOptions>
     */
    private $messageOptions = [];

    public function __construct(LanguageAwareConfigurationInterface $configuration, SerializerInterface $serializer, ?LoggerInterface $logger = null)
    {
        $this->configuration = $configuration;
        $this->serializer = $serializer;
        $this->logger = $logger;
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
     *
     * @deprecated since 3.4.0
     */
    public function getAvailablePaymentOptions(?int $shopId = null): array
    {
        @trigger_error(sprintf('Method "%s()" is deprecated since version 3.4.0.', __METHOD__), \E_USER_DEPRECATED);

        return [];
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
            $this->getStatusDescriptionMap()
        );

        return $configuration
            ->setCashOnDeliveryStatusId($this->getCashOnDeliveryStatusId())
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

        return \is_array($map) ? $map : [];
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
        if (\is_callable([$configuration, 'getMessageOptions'])) {
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
}
