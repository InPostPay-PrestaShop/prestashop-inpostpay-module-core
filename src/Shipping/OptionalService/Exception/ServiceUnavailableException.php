<?php

declare(strict_types=1);

namespace izi\prestashop\Shipping\OptionalService\Exception;

class ServiceUnavailableException extends \Exception
{
    /**
     * @var string
     */
    private $serviceCode;

    public function __construct(string $serviceCode, ?string $message = null)
    {
        parent::__construct($message ?? \sprintf('Service "%s" is unavailable.', $serviceCode));
        $this->serviceCode = $serviceCode;
    }

    public function getServiceCode(): string
    {
        return $this->serviceCode;
    }
}
