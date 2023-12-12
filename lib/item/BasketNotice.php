<?php

namespace izi\item;

class BasketNotice extends \izi\Item
{
    public const TYPE_ERROR = 'ERROR';
    public const TYPE_ATTENTION = 'ATTENTION';

    /**
     * @var self::TYPE_ERROR|self::TYPE_ATTENTION
     */
    protected $type;

    /**
     * @var string
     */
    protected $description;

    private function __construct(string $type, string $description)
    {
        $this->type = $type;
        $this->description = $description;
    }

    /**
     * @return self::TYPE_ERROR|self::TYPE_ATTENTION
     */
    public function getType(): string
    {
        return $this->type;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public static function error(string $description): self
    {
        return new self(self::TYPE_ERROR, $description);
    }

    public static function attention(string $description): self
    {
        return new self(self::TYPE_ATTENTION, $description);
    }
}
