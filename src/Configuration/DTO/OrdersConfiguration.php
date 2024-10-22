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
     * @var MessageOptions|null
     *
     * @Assert\Valid()
     */
    private $messageOptions;

    /**
     * @param string|null $posId
     * @param array<int, array<int, string>> $statusDescriptionMap
     * @param PaymentType[] $availablePaymentOptions
     */
    public function __construct(?int $initialStatusId = null, ?int $paidStatusId = null, /* ?bool $bankPaymentEnabled = null, ?bool $carrierPaymentEnabled = null, */ $posId = null, $statusDescriptionMap = [], $availablePaymentOptions = [])
    {
        [$posId, $statusDescriptionMap, $availablePaymentOptions] = $this->normalizeConstructorArguments(func_get_args(), func_num_args());

        $this->defaultInitialStatusId = $initialStatusId;
        $this->paidStatusId = $paidStatusId;
        $this->setPointOfSaleId($posId);
        $this->setStatusDescriptionMap($statusDescriptionMap);
        $this->setAvailablePaymentOptions($availablePaymentOptions);
    }

    /**
     * @param PaymentType|int|null $paymentType
     */
    public function getInitialStatusId($paymentType = null, ?int $shopId = null): ?int
    {
        if (is_int($paymentType)) {
            @trigger_error(sprintf('Passing $shopId as the first argument of "%s::%s()" is deprecated.', OrdersConfigurationInterface::class, __METHOD__), \E_USER_DEPRECATED);

            $paymentType = null;
        }

        if (null !== $paymentType && !$paymentType instanceof PaymentType) {
            throw new \InvalidArgumentException(sprintf('Expected $paymentType to be an instance of "%", "%s" given.', PaymentType::class, get_debug_type($paymentType)));
        }

        if (PaymentType::CashOnDelivery() === $paymentType) {
            return $this->cashOnDeliveryStatusId;
        }

        return $this->defaultInitialStatusId;
    }

    /**
     * @deprecated
     */
    public function setInitialStatusId(?\OrderState $initialStatus): self
    {
        @trigger_error(sprintf('Method "%s:%s()" is deprecated.', __CLASS__, __METHOD__), \E_USER_DEPRECATED);

        return $this->setDefaultInitialStatusId($initialStatus);
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

    /**
     * @deprecated
     */
    public function isBankPaymentEnabled(): bool
    {
        return [] !== array_uintersect($this->availablePaymentOptions, PaymentType::getBankProvidedPaymentOptions(), [Enum::class, 'compareValues']);
    }

    public function isCarrierPaymentEnabled(): bool
    {
        return [] !== array_uintersect($this->availablePaymentOptions, PaymentType::getCarrierProvidedPaymentOptions(), [Enum::class, 'compareValues']);
    }

    public function setCarrierPaymentEnabled(?bool $carrierPaymentEnabled): self
    {
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

    private function normalizeConstructorArguments(array $arguments, int $numberOfArguments): array
    {
        if (2 >= $numberOfArguments) {
            return [null, [], []];
        }

        if (4 <= $numberOfArguments && is_array($arguments[3])) {
            return [$arguments[2], $arguments[3], $arguments[4] ?? []];
        }

        $posId = null;
        $statusDescriptionMap = $arguments[5] ?? [];
        $availablePaymentOptions = [];

        $bankPaymentEnabled = null;

        if (3 <= $numberOfArguments) {
            if (!is_string($arguments[2] ?? '')) {
                $bankPaymentEnabled = $arguments[2];
                $posId = $arguments[4] ?? null;
            } else {
                $posId = $arguments[2];
            }
        }

        if (null !== $bankPaymentEnabled || 4 <= $numberOfArguments) {
            @trigger_error(sprintf('Passing the $bankPaymentEnabled as 3rd argument or the $carrierPaymentEnabled as 4th argument for "%s::__construct()" is deprecated.', self::class));

            $availablePaymentOptions = $this->normalizeAvailablePaymentOptions($bankPaymentEnabled ?? false, $arguments[3] ?? false);
        }

        return [$posId, $statusDescriptionMap, $availablePaymentOptions];
    }

    private function normalizeAvailablePaymentOptions(bool $bankPaymentEnabled, bool $carrierPaymentEnabled): array
    {
        if ($bankPaymentEnabled && $carrierPaymentEnabled) {
            return PaymentType::cases();
        }

        if ($bankPaymentEnabled) {
            return PaymentType::getBankProvidedPaymentOptions();
        }

        if ($carrierPaymentEnabled) {
            return PaymentType::getCarrierProvidedPaymentOptions();
        }

        return [];
    }
}
