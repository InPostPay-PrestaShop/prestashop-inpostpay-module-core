<?php

declare(strict_types=1);

namespace izi\prestashop\Handler\Result;

use izi\prestashop\BasketApp\Basket\Response\QrCode;
use izi\prestashop\Common\Error\Error;

final class BasketBindingResult
{
    /**
     * @var string
     */
    private $basketId;

    /**
     * @var QrCode|Error|array
     */
    private $data;

    /**
     * @param QrCode|Error|array $data
     */
    private function __construct(string $basketId, $data = [])
    {
        $this->basketId = $basketId;
        $this->data = $data;
    }

    public static function success(string $basketId): self
    {
        return new self($basketId);
    }

    public static function error(string $basketId, Error $error): self
    {
        return new self($basketId, $error);
    }

    public static function qrCode(string $basketId, QrCode $qrCode): self
    {
        return new self($basketId, $qrCode);
    }

    public function getBasketId(): string
    {
        return $this->basketId;
    }

    /**
     * @return QrCode|Error|array
     */
    public function getData()
    {
        return $this->data;
    }
}
