<?php

declare(strict_types=1);

namespace izi\prestashop\Common\Customer;

use izi\prestashop\Common\PhoneNumber;

final class AccountInfo implements \JsonSerializable
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $surname;

    /**
     * @var PhoneNumber
     */
    private $phone_number;

    /**
     * @var string
     */
    private $mail;

    /**
     * @var ClientAddress
     */
    private $client_address;

    public function __construct(string $name, string $surname, PhoneNumber $phone_number, string $mail, ClientAddress $client_address)
    {
        $this->name = $name;
        $this->surname = $surname;
        $this->phone_number = $phone_number;
        $this->mail = $mail;
        $this->client_address = $client_address;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getSurname(): string
    {
        return $this->surname;
    }

    public function getPhoneNumber(): PhoneNumber
    {
        return $this->phone_number;
    }

    public function getEmail(): string
    {
        return $this->mail;
    }

    public function getAddress(): ClientAddress
    {
        return $this->client_address;
    }

    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
