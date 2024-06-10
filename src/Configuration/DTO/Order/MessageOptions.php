<?php

declare(strict_types=1);

namespace izi\prestashop\Configuration\DTO\Order;

use izi\prestashop\Order\Message\MessageFormatterInterface;
use izi\prestashop\Validator\ProcessableMessageFormat;
use Symfony\Component\Validator\Constraints as Assert;

/* @interal */
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
    private $appendIfApmDelivery;

    /**
     * @var bool
     */
    private $customFormat;

    public function __construct(?string $message = '', bool $appendIfApmDelivery = false, bool $customFormat = false)
    {
        $this->message = $message;
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

        if (!$this->appendIfApmDelivery) {
            return MessageFormatterInterface::DEFAULT_FORMAT;
        }

        return sprintf("%s\n\n{%% if \"APM\" == delivery_type %%}\n%s\n{%% endif %%}", MessageFormatterInterface::DEFAULT_FORMAT, $this->message);
    }

    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
