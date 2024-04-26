<?php

declare(strict_types=1);

namespace izi\prestashop\Handler\Result;

final class OrderEvent implements \JsonSerializable, \Stringable
{
    private const ACTION_REFRESH = 'refresh';
    private const ACTION_REDIRECT = 'redirect';

    /**
     * @var string
     */
    private $action;

    /**
     * @var string|null
     */
    private $url;

    private function __construct(string $action, ?string $url = null)
    {
        $this->action = $action;
        $this->url = $url;
    }

    public static function refresh(): self
    {
        return new self(self::ACTION_REFRESH);
    }

    public static function redirect(string $url): self
    {
        return new self(self::ACTION_REDIRECT, $url);
    }

    public function jsonSerialize(): array
    {
        return self::ACTION_REDIRECT === $this->action
            ? [
                'action' => $this->action,
                'url' => $this->url,
            ]
            : ['action' => $this->action];
    }

    public function __toString(): string
    {
        return json_encode($this);
    }
}
