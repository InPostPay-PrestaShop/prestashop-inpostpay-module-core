<?php

declare(strict_types=1);

namespace izi\prestashop\Configuration;

use izi\prestashop\Environment\EnvironmentInterface;
use izi\prestashop\OAuth2\Authentication\ClientCredentialsRepositoryInterface;

interface ApiConfigurationInterface extends ClientCredentialsRepositoryInterface
{
    public function getEnvironment(): EnvironmentInterface;

    public function getMerchantClientId(): ?string;
}
