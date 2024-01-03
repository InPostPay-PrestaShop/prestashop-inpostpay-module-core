<?php

declare(strict_types=1);

namespace izi\prestashop\Configuration;

use izi\prestashop\Environment\EnvironmentInterface;
use izi\prestashop\Environment\EnvironmentType;
use izi\prestashop\OAuth2\Authentication\ClientCredentialsRepositoryInterface;

interface ApiConfigurationInterface extends ClientCredentialsRepositoryInterface
{
    public function getEnvironmentType(): EnvironmentType;

    public function getEnvironment(): EnvironmentInterface;
}
