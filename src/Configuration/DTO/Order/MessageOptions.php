<?php

declare(strict_types=1);

namespace izi\prestashop\Configuration\DTO\Order;

use izi\prestashop\Order\Message\MessageFormatterInterface;
use izi\prestashop\Validator\ProcessableMessageFormat;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @internal
 */
final class MessageOptions implements \JsonSerializable
{
    /**
     * @var string|null
     */
    private $message;

    /**
     * @var bool|null
     *
     * @Assert\NotNull()
     */
    private $appendDeliveryEmail;

    /**
     * @var bool|null
     *
     * @Assert\NotNull()
     */
    private $appendIfApmDelivery;

    /**
     * @var bool
     */
    private $customFormat;

    public function __construct(?string $message = '', bool $appendDeliveryEmail = true, bool $appendIfApmDelivery = false, bool $customFormat = false)
    {
        $this->message = $message;
        $this->appendDeliveryEmail = $appendDeliveryEmail;
        $this->appendIfApmDelivery = $appendIfApmDelivery;
        $this->customFormat = $customFormat;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(?string $message): self
    {
        $this->message = $message;

        return $this;
    }

    public function getAppendDeliveryEmail(): ?bool
    {
        return $this->appendDeliveryEmail;
    }

    public function setAppendDeliveryEmail(?bool $appendDeliveryEmail): self
    {
        $this->appendDeliveryEmail = $appendDeliveryEmail;

        return $this;
    }

    public function getAppendIfApmDelivery(): ?bool
    {
        return $this->appendIfApmDelivery;
    }

    public function setAppendIfApmDelivery(?bool $appendIfApmDelivery): self
    {
        $this->appendIfApmDelivery = $appendIfApmDelivery;

        return $this;
    }

    public function isCustomFormat(): bool
    {
        return $this->customFormat;
    }

    /**
     * @ProcessableMessageFormat
     */
    public function getFormat(): string
    {
        if ($this->customFormat) {
            return (string) $this->message;
        }

        $format = MessageFormatterInterface::DEFAULT_FORMAT;
        if ($this->appendDeliveryEmail) {
            $format .= "\n\nUnikalny maskowany adres e-mail: {delivery_email}";
        }

        if (!$this->appendIfApmDelivery) {
            return $format;
        }

        return \sprintf("%s\n\n{%% if \"APM\" == delivery_type %%}\n%s\n{%% endif %%}", $format, $this->message);
    }

    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
