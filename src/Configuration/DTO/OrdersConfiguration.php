<?php

declare(strict_types=1);

namespace izi\prestashop\Configuration\DTO;

use izi\prestashop\Common\PaymentType;
use izi\prestashop\Configuration\DTO\Order\MessageOptions;
use izi\prestashop\Configuration\OrdersConfigurationInterface;
use izi\prestashop\Enum\Enum;
use Symfony\Component\Validator\Constraints as Assert;

final class OrdersConfiguration implements OrdersConfigurationInterface
{
    /**
     * @var int|null
     *
     * @Assert\NotNull()
     *
     * @Assert\GreaterThan(0)
     */
    private $defaultInitialStatusId;

    /**
     * @var int|null
     *
     * @Assert\NotNull()
     *
     * @Assert\GreaterThan(0)
     */
    private $cashOnDeliveryStatusId;

    /**
     * @var int|null
     *
     * @Assert\NotNull()
     *
     * @Assert\GreaterThan(0)
     */
    private $paidStatusId;

    /**
     * @var array<int, array<int, string>>
     *
     * @Assert\All(
     *     @Assert\All(
     *         @Assert\Type("string"),
     *     )
     * )
     */
    private $statusDescriptionMap;

    /**
     * @var string|null
     *
     * @Assert\NotBlank()
     */
    private $posId;

    /**
     * @var PaymentType[]
     *
     * @Assert\All(
     *     @Assert\Type(PaymentType::class),
     * )
     */
    private $availablePaymentOptions;

    /**
     * @var bool|null
     *
     * @Assert\NotNull()
     */
    private $allPaymentOptionsEnabled = true;

    /**
     * @var MessageOptions|null
     *
     * @Assert\Valid()
     */
    private $messageOptions;

    /**
     * @param array<int, array<int, string>> $statusDescriptionMap
     * @param PaymentType[] $availablePaymentOptions
     */
    public function __construct(?int $initialStatusId = null, ?int $paidStatusId = null, ?string $posId = null, array $statusDescriptionMap = [], array $availablePaymentOptions = [])
    {
        $this->defaultInitialStatusId = $initialStatusId;
        $this->paidStatusId = $paidStatusId;
        $this->posId = $posId;
        $this->statusDescriptionMap = $statusDescriptionMap;
        $this->availablePaymentOptions = $availablePaymentOptions;
    }

    public function getInitialStatusId(?PaymentType $paymentType = null, ?int $shopId = null): ?int
    {
        if (PaymentType::CashOnDelivery() === $paymentType) {
            return $this->cashOnDeliveryStatusId;
        }

        return $this->defaultInitialStatusId;
    }

    public function getDefaultInitialStatusId(): ?int
    {
        return $this->defaultInitialStatusId;
    }

    public function setDefaultInitialStatusId(?\OrderState $initialStatus): self
    {
        $this->defaultInitialStatusId = null === $initialStatus ? null : (int) $initialStatus->id;

        return $this;
    }

    public function getCashOnDeliveryStatusId(): ?int
    {
        return $this->cashOnDeliveryStatusId;
    }

    public function setCashOnDeliveryStatusId(?\OrderState $codStatus): self
    {
        $this->cashOnDeliveryStatusId = null === $codStatus ? null : (int) $codStatus->id;

        return $this;
    }

    public function getPaidStatusId(?int $shopId = null): ?int
    {
        return $this->paidStatusId;
    }

    public function setPaidStatusId(?\OrderState $paidStatus): self
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

    public function setStatusDescriptionMap(array $statusDescriptionMap): self
    {
        $this->statusDescriptionMap = $statusDescriptionMap;

        return $this;
    }

    public function getAvailablePaymentOptions(?int $shopId = null): array
    {
        return $this->availablePaymentOptions;
    }

    /**
     * @param PaymentType[] $availablePaymentOptions
     */
    public function setAvailablePaymentOptions(array $availablePaymentOptions): self
    {
        $this->availablePaymentOptions = $availablePaymentOptions;

        return $this;
    }

    public function isAllPaymentOptionsEnabled(): ?bool
    {
        return $this->allPaymentOptionsEnabled;
    }

    public function setAllPaymentOptionsEnabled(?bool $enabled): self
    {
        $this->allPaymentOptionsEnabled = $enabled;

        return $this;
    }

    /**
     * @deprecated use {@see getAvailablePaymentOptions()} instead
     */
    public function isCarrierPaymentEnabled(): bool
    {
        @trigger_error(sprintf('Method "%s()" is deprecated. Use "getAvailablePaymentOptions()" instead.', __METHOD__), \E_USER_DEPRECATED);

        return [] !== array_uintersect($this->availablePaymentOptions, PaymentType::getCarrierProvidedPaymentOptions(), [Enum::class, 'compareValues']);
    }

    /**
     * @deprecated use {@see setAvailablePaymentOptions()} instead
     */
    public function setCarrierPaymentEnabled(?bool $carrierPaymentEnabled): self
    {
        @trigger_error(sprintf('Method "%s()" is deprecated. Use "setAvailablePaymentOptions()" instead.', __METHOD__), \E_USER_DEPRECATED);

        $paymentOptions = PaymentType::getCarrierProvidedPaymentOptions();

        if ($carrierPaymentEnabled) {
            $this->availablePaymentOptions = array_unique(array_merge($this->availablePaymentOptions, $paymentOptions), SORT_REGULAR);
        } else {
            $this->availablePaymentOptions = array_filter($this->availablePaymentOptions, static function (PaymentType $type) use ($paymentOptions): bool {
                return !in_array($type, $paymentOptions, true);
            });
        }

        return $this;
    }

    public function getPointOfSaleId(?int $shopId = null): ?string
    {
        return $this->posId;
    }

    public function setPointOfSaleId(?string $posId): self
    {
        $this->posId = $posId;

        return $this;
    }

    /**
     * @internal
     */
    public function getMessageOptions(): MessageOptions
    {
        return $this->messageOptions ?? ($this->messageOptions = new MessageOptions());
    }

    /**
     * @internal
     */
    public function setMessageOptions(MessageOptions $options): self
    {
        $this->messageOptions = $options;

        return $this;
    }

    public function getMessageFormat(?int $shopId = null): string
    {
        return $this->getMessageOptions()->getFormat();
    }

    /**
     * @Assert\IsTrue(message="At least one payment option must be enabled.")
     *
     * @internal
     */
    public function hasEnabledPaymentOptions(): bool
    {
        return $this->allPaymentOptionsEnabled || [] !== $this->availablePaymentOptions;
    }
}
