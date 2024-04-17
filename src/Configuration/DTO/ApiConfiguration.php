<?php

declare(strict_types=1);

namespace izi\prestashop\Configuration\DTO;

use izi\prestashop\Configuration\ApiConfigurationInterface;
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

    public function __construct(?EnvironmentType $environmentType = null, ?ClientCredentialsInterface $clientCredentials = null)
    {
        $this->environmentType = $environmentType;
        $this->clientCredentials = $clientCredentials;
    }

    public function getEnvironment(): EnvironmentInterface
    {
        return $this->getEnvironmentType()->createEnvironment();
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
}
