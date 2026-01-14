<?php

declare(strict_types=1);

namespace izi\prestashop\Configuration\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final class HtmlStyles implements \IteratorAggregate, \JsonSerializable
{
    /**
     * @var int|null
     *
     * @Assert\GreaterThanOrEqual(0)
     */
    private $marginLeft;

    /**
     * @var int|null
     *
     * @Assert\GreaterThanOrEqual(0)
     */
    private $marginRight;

    /**
     * @var int|null
     *
     * @Assert\GreaterThanOrEqual(0)
     */
    private $marginTop;

    /**
     * @var int|null
     *
     * @Assert\GreaterThanOrEqual(0)
     */
    private $marginBottom;

    /**
     * @var string|null
     *
     * @Assert\Choice(choices={"start", "center", "end"})
     */
    private $justifyContent;

    /**
     * @internal
     */
    public static function getJustifyContentStyleByAlignment(string $alignment): ?string
    {
        switch ($alignment) {
            case 'left':
                return 'start';
            case 'center':
                return 'center';
            case 'right':
                return 'end';
            default:
                return null;
        }
    }

    public function getMarginLeft(): ?int
    {
        return $this->marginLeft;
    }

    public function setMarginLeft(?int $marginLeft): self
    {
        $this->marginLeft = $marginLeft;

        return $this;
    }

    public function getMarginRight(): ?int
    {
        return $this->marginRight;
    }

    public function setMarginRight(?int $marginRight): self
    {
        $this->marginRight = $marginRight;

        return $this;
    }

    public function getMarginTop(): ?int
    {
        return $this->marginTop;
    }

    public function setMarginTop(?int $marginTop): self
    {
        $this->marginTop = $marginTop;

        return $this;
    }

    public function getMarginBottom(): ?int
    {
        return $this->marginBottom;
    }

    public function setMarginBottom(?int $marginBottom): self
    {
        $this->marginBottom = $marginBottom;

        return $this;
    }

    public function getJustifyContent(): ?string
    {
        return $this->justifyContent;
    }

    /**
     * @return $this
     */
    public function setJustifyContent(?string $justifyContent): self
    {
        $this->justifyContent = $justifyContent;

        return $this;
    }

    /**
     * @return \Generator
     */
    public function getIterator(): \Generator
    {
        if (null !== $this->marginLeft) {
            yield 'margin-left' => \sprintf('%dpx', $this->marginLeft);
        }

        if (null !== $this->marginRight) {
            yield 'margin-right' => \sprintf('%dpx', $this->marginRight);
        }

        if (null !== $this->marginTop) {
            yield 'margin-top' => \sprintf('%dpx', $this->marginTop);
        }

        if (null !== $this->marginBottom) {
            yield 'margin-bottom' => \sprintf('%dpx', $this->marginBottom);
        }

        if (null !== $this->justifyContent) {
            yield 'display' => 'flex';
            yield 'flex-wrap' => 'wrap';
            yield 'justify-content' => $this->justifyContent;
        }
    }

    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
