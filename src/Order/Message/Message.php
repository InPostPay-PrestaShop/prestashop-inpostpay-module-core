<?php

declare(strict_types=1);

namespace izi\prestashop\Order\Message;

final class Message
{
    /**
     * @var string
     */
    private $message;

    /**
     * @var array<string, mixed>
     */
    private $parameters;

    /**
     * @param array<string, mixed> $parameters
     */
    public function __construct(string $message, array $parameters)
    {
        $this->message = $message;
        $this->parameters = $parameters;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * @return array<string, mixed>
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }
}
