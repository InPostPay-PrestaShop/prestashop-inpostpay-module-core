<?php

declare(strict_types=1);

namespace izi\prestashop\Http\Response;

/**
 * @template T of (string|\Stringable)
 */
final class ServerSentEventBuilder
{
    /**
     * @var string|null
     */
    private $id;

    /**
     * @var string|null
     */
    private $event;

    /**
     * @var T|null
     */
    private $data;

    /**
     * @var int|null
     */
    private $retry;

    /**
     * @var string|null
     */
    private $comment;

    public static function create(): self
    {
        return new self();
    }

    public function setId(?string $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function setEventName(?string $name): self
    {
        $this->event = $name;

        return $this;
    }

    /**
     * @param T|null $data
     */
    public function setData($data): self
    {
        $this->data = $data;

        return $this;
    }

    public function setRetry(?int $delayMs): self
    {
        $this->retry = $delayMs;

        return $this;
    }

    public function setComment(?string $comment): self
    {
        $this->comment = $comment;

        return $this;
    }

    /**
     * @return ServerSentEvent<T>
     */
    public function build(): ServerSentEvent
    {
        return new ServerSentEvent($this->id, $this->event, $this->data, $this->retry, $this->comment);
    }
}
