<?php

declare(strict_types=1);

namespace izi\prestashop\Configuration\DTO;

use izi\prestashop\Configuration\ApiConfigurationInterface;
use izi\prestashop\Environment\EnvironmentFactory;
use izi\prestashop\Environment\EnvironmentInterface;
use izi\prestashop\Environment\EnvironmentType;
use izi\prestashop\OAuth2\Authentication\ClientCredentialsInterface;
use Symfony\Component\Validator\Constraints as Assert;

final class ApiConfiguration implements ApiConfigurationInterface
{
    /**
     * @var EnvironmentType|null
     *
     * @Assert\NotNull()
     */
    private $environmentType;

    /**
     * @var ClientCredentialsInterface|null
     *
     * @Assert\Valid()
     */
    private $clientCredentials;

    /**
     * @var bool|null
     *
     * @Assert\NotNull()
     */
    private $widgetV2 = true;

    /**
     * @var string|null
     *
     * @Assert\Expression(expression="not this.isWidgetV2() or value", message="Merchant client ID is required by Widget 2.0")
     */
    private $merchantClientId;

    public function __construct(?EnvironmentType $environmentType = null, ?ClientCredentialsInterface $clientCredentials = null)
    {
        $this->environmentType = $environmentType;
        $this->clientCredentials = $clientCredentials;
    }

    public function getEnvironment(): EnvironmentInterface
    {
        $type = $this->getEnvironmentType();
        $widgetV2 = $this->widgetV2 ?? true;

        return (new EnvironmentFactory())->createEnvironment($type, $widgetV2);
    }

    public function getEnvironmentType(): EnvironmentType
    {
        return $this->environmentType ?? EnvironmentType::Production();
    }

    public function setEnvironmentType(?EnvironmentType $environmentType): self
    {
        $this->environmentType = $environmentType;

        return $this;
    }

    public function getClientCredentials(): ?ClientCredentialsInterface
    {
        return $this->clientCredentials;
    }

    public function setClientCredentials(?ClientCredentialsInterface $clientCredentials): self
    {
        $this->clientCredentials = $clientCredentials;

        return $this;
    }

    /**
     * @internal
     */
    public function isWidgetV2(): ?bool
    {
        return $this->widgetV2;
    }

    /**
     * @internal
     */
    public function setWidgetV2(?bool $isV2): self
    {
        $this->widgetV2 = $isV2;

        return $this;
    }

    public function getMerchantClientId(): string
    {
        return (string) $this->merchantClientId;
    }

    public function setMerchantClientId(?string $merchantClientId): ApiConfiguration
    {
        $this->merchantClientId = $merchantClientId;

        return $this;
    }
}
