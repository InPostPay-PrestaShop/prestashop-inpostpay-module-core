<?php

declare(strict_types=1);

namespace izi\prestashop\Http\Response;

/**
 * @template T of (string|\Stringable)
 */
final class ServerSentEvent
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

    /**
     * @var string|null
     */
    private $message;

    /**
     * @param T|null $data
     */
    public function __construct(?string $id, ?string $event, $data, ?int $retry, ?string $comment)
    {
        if (null !== $retry && 0 >= $retry) {
            throw new \DomainException('Delay should be greater than 0.');
        }

        $this->id = $id;
        $this->event = $event;
        $this->data = $data;
        $this->retry = $retry;
        $this->comment = $comment;
    }

    public static function builder(): ServerSentEventBuilder
    {
        return new ServerSentEventBuilder();
    }

    public function getMessage(): string
    {
        if (isset($this->message)) {
            return $this->message;
        }

        $lines = [];

        if (null !== $this->comment) {
            $lines[] = ": $this->comment";
        }

        if (null !== $this->id) {
            $lines[] = "id: $this->id";
        }

        if (null !== $this->event) {
            $lines[] = "event: $this->event";
        }

        if (null !== $this->data) {
            $data = (string) $this->data;
            $lines[] = "data: $data";
        }

        if (null !== $this->retry) {
            $lines[] = "retry: $this->retry";
        }

        return $this->message = implode("\n", $lines) . "\n\n";
    }

    public function __toString(): string
    {
        return $this->getMessage();
    }
}
