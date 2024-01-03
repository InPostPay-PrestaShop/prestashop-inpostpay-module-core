<?php

declare(strict_types=1);

namespace izi\prestashop\Common\Error;

final class Error implements \JsonSerializable
{
    /**
     * @var string
     */
    private $error_code;

    /**
     * @var string
     */
    private $error_message;

    public function __construct(string $error_code, string $error_message)
    {
        $this->error_code = $error_code;
        $this->error_message = $error_message;
    }

    public function getCode(): string
    {
        return $this->error_code;
    }

    public function getMessage(): string
    {
        return $this->error_message;
    }

    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
