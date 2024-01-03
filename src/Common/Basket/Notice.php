<?php

declare(strict_types=1);

namespace izi\prestashop\Common\Basket;

final class Notice implements \JsonSerializable
{

    /**
     * @var NoticeType
     */
    private $type;

    /**
     * @var string
     */
    private $description;

    private function __construct(NoticeType $type, string $description)
    {
        $this->type = $type;
        $this->description = $description;
    }

    public static function error(string $description): self
    {
        return new self(NoticeType::Error(), $description);
    }

    public static function attention(string $description): self
    {
        return new self(NoticeType::Attention(), $description);
    }

    public function getType(): NoticeType
    {
        return $this->type;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function jsonSerialize()
    {
        return get_object_vars($this);
    }
}
