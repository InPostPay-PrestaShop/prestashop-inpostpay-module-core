<?php

declare(strict_types=1);

namespace izi\prestashop\BasketApp\Basket\Request;

final class Browser implements \JsonSerializable
{
    /**
     * @var string
     */
    private $user_agent;

    /**
     * @var string|null
     */
    private $description;

    /**
     * @var string
     */
    private $platform;

    /**
     * @var string
     */
    private $architecture;

    /**
     * @var \DateTimeImmutable
     */
    private $data_time;

    /**
     * @var string
     */
    private $location;

    /**
     * @var string
     */
    private $customer_ip;

    /**
     * @var string
     */
    private $port;

    public function __construct(string $user_agent, string $platform, string $architecture, \DateTimeImmutable $data_time, string $customer_ip, string $port, string $location = '-', string $description = null)
    {
        $this->user_agent = $user_agent;
        $this->description = $description;
        $this->platform = $platform;
        $this->architecture = $architecture;
        $this->data_time = $data_time;
        $this->location = $location;
        $this->customer_ip = $customer_ip;
        $this->port = $port;
    }

    public function getUserAgent(): string
    {
        return $this->user_agent;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getPlatform(): string
    {
        return $this->platform;
    }

    public function getArchitecture(): string
    {
        return $this->architecture;
    }

    public function getDateTime(): \DateTimeImmutable
    {
        return $this->data_time;
    }

    public function getLocation(): string
    {
        return $this->location;
    }

    public function getCustomerIp(): string
    {
        return $this->customer_ip;
    }

    public function getPort(): string
    {
        return $this->port;
    }

    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
