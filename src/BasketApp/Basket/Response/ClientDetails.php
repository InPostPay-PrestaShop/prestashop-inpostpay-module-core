<?php

declare(strict_types=1);

namespace izi\prestashop\BasketApp\Basket\Response;

use izi\prestashop\Common\PhoneNumber;

final class ClientDetails implements \JsonSerializable
{
    /**
     * @var PhoneNumber
     */
    private $phone_number;

    /**
     * @var string
     */
    private $masked_phone_number;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $surname;

    public function __construct(PhoneNumber $phone_number, string $masked_phone_number, string $name, string $surname)
    {
        $this->phone_number = $phone_number;
        $this->masked_phone_number = $masked_phone_number;
        $this->name = $name;
        $this->surname = $surname;
    }

    public function getPhoneNumber(): PhoneNumber
    {
        return $this->phone_number;
    }

    public function getMaskedPhoneNumber(): string
    {
        return $this->masked_phone_number;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getSurname(): string
    {
        return $this->surname;
    }

    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
