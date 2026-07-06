<?php

declare(strict_types=1);

namespace izi\prestashop\Configuration\DTO;

use izi\prestashop\Common\PaymentType;
use izi\prestashop\Configuration\DTO\Order\MessageOptions;
use izi\prestashop\Configuration\OrdersConfigurationInterface;
use Symfony\Component\Validator\Constraints as Assert;

final class OrdersConfiguration implements OrdersConfigurationInterface
{
    /**
     * @var int|null
     *
     * @Assert\NotNull()
     * @Assert\GreaterThan(0)
     */
    private $defaultInitialStatusId;

    /**
     * @var int|null
     *
     * @Assert\NotNull()
     * @Assert\GreaterThan(0)
     */
    private $cashOnDeliveryStatusId;

    /**
     * @var int|null
     *
     * @Assert\NotNull()
     * @Assert\GreaterThan(0)
     */
    private $paidStatusId;

    /**
     * @var array<int, array<int, string>>
     *
     * @Assert\All(
     *     @Assert\All(
     *         @Assert\Type("string"),
     *     ),
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
     * @var PaymentType[]|null
     */
    private $availablePaymentOptions;

    /**
     * @var bool|null
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
     * @param PaymentType[]|null $availablePaymentOptions
     */
    public function __construct(?int $initialStatusId = null, ?int $paidStatusId = null, ?string $posId = null, array $statusDescriptionMap = [], ?array $availablePaymentOptions = null)
    {
        if (null !== $availablePaymentOptions) {
            @trigger_error(\sprintf('Passing $availablePaymentOptions to "%s()" is deprecated since version 3.4.0.', __METHOD__), \E_USER_DEPRECATED);
        }

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

    public function setDefaultInitialStatusId(?int $initialStatusId): self
    {
        $this->defaultInitialStatusId = $initialStatusId;

        return $this;
    }

    public function getCashOnDeliveryStatusId(): ?int
    {
        return $this->cashOnDeliveryStatusId;
    }

    public function setCashOnDeliveryStatusId(?int $codStatusId): self
    {
        $this->cashOnDeliveryStatusId = $codStatusId;

        return $this;
    }

    public function getPaidStatusId(?int $shopId = null): ?int
    {
        return $this->paidStatusId;
    }

    public function setPaidStatusId(?int $paidStatusId): self
    {
        $this->paidStatusId = $paidStatusId;

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

    /**
     * @deprecated since 3.4.0
     */
    public function getAvailablePaymentOptions(?int $shopId = null): array
    {
        $this->triggerDeprecation(__METHOD__);

        return $this->availablePaymentOptions ?? [];
    }

    /**
     * @param PaymentType[] $availablePaymentOptions
     *
     * @deprecated since 3.4.0
     */
    public function setAvailablePaymentOptions(array $availablePaymentOptions): self
    {
        $this->triggerDeprecation(__METHOD__);
        $this->availablePaymentOptions = $availablePaymentOptions;

        return $this;
    }

    /**
     * @deprecated since 3.4.0
     */
    public function isAllPaymentOptionsEnabled(): bool
    {
        $this->triggerDeprecation(__METHOD__);

        return $this->allPaymentOptionsEnabled;
    }

    /**
     * @deprecated since 3.4.0
     */
    public function setAllPaymentOptionsEnabled(?bool $enabled): self
    {
        $this->triggerDeprecation(__METHOD__);
        $this->allPaymentOptionsEnabled = $enabled;

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

    private function triggerDeprecation(string $method): void
    {
        @trigger_error(sprintf('Method "%s()" is deprecated since version 3.4.0.', $method), \E_USER_DEPRECATED);
    }
}
