<?php

declare(strict_types=1);

namespace izi\prestashop\Configuration;

use izi\prestashop\Environment\EnvironmentInterface;
use izi\prestashop\Environment\EnvironmentType;
use izi\prestashop\OAuth2\Authentication\ClientCredentials;
use izi\prestashop\OAuth2\Authentication\ClientCredentialsInterface;
use izi\prestashop\OAuth2\Token\AccessTokenInterface;
use izi\prestashop\OAuth2\Token\AccessTokenRepositoryInterface;
use izi\prestashop\OAuth2\Token\BearerToken;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\SerializerInterface;

final class ApiConfiguration implements ApiConfigurationInterface, AccessTokenRepositoryInterface, PersistentConfigurationInterface
{
    private const ENVIRONMENT_TYPE = 'INPOST_PAY_environment';
    private const OAUTH2_CLIENT_ID = 'INPOST_PAY_client_id';
    public const OAUTH2_CLIENT_SECRET = 'INPOST_PAY_client_secret';
    public const ACCESS_TOKEN = 'INPOST_PAY_ACCESS_TOKEN';

    /**
     * @var ConfigurationInterface
     */
    private $configuration;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var EnvironmentInterface|null
     */
    private $environment;

    /**
     * @var ClientCredentialsInterface|null
     */
    private $clientCredentials;

    /**
     * @var AccessTokenInterface|null
     */
    private $accessToken;

    public function __construct(ConfigurationInterface $configuration, SerializerInterface $serializer)
    {
        $this->configuration = $configuration;
        $this->serializer = $serializer;
    }

    public function getEnvironmentType(): EnvironmentType
    {
        $value = (int) $this->configuration->get(self::ENVIRONMENT_TYPE);

        return EnvironmentType::tryFrom($value) ?? EnvironmentType::Production();
    }

    public function getEnvironment(): EnvironmentInterface
    {
        return $this->environment ?? ($this->environment = $this->getEnvironmentType()->createEnvironment());
    }

    public function getClientCredentials(): ?ClientCredentialsInterface
    {
        if (isset($this->clientCredentials)) {
            return $this->clientCredentials;
        }

        $clientId = $this->configuration->get(self::OAUTH2_CLIENT_ID);
        $clientSecret = $this->configuration->get(self::OAUTH2_CLIENT_SECRET);

        if (null === $clientId || null === $clientSecret) {
            return null;
        }

        return $this->clientCredentials = new ClientCredentials($clientId, $clientSecret);
    }

    public function getToken(): ?AccessTokenInterface
    {
        if (isset($this->accessToken)) {
            return $this->accessToken;
        }

        $value = $this->configuration->get(self::ACCESS_TOKEN);

        if (null === $value) {
            return null;
        }

        try {
            return $this->accessToken = $this->serializer->deserialize($value, BearerToken::class, 'json');
        } catch (ExceptionInterface $e) {
            return null;
        }
    }

    public function saveToken(AccessTokenInterface $accessToken): void
    {
        $value = $this->serializer->serialize($accessToken, 'json');
        $this->configuration->set(self::ACCESS_TOKEN, $value);
        $this->accessToken = $accessToken;
    }

    public function deleteToken(): void
    {
        $this->configuration->set(self::ACCESS_TOKEN, null);
        $this->accessToken = null;
    }

    public function copy(): ApiConfigurationInterface
    {
        return new DTO\ApiConfiguration(
            $this->getEnvironmentType(),
            $this->getClientCredentials()
        );
    }

    public function persist(ApiConfigurationInterface $configuration): void
    {
        $this->setEnvironmentType($configuration->getEnvironmentType());
        $this->setClientCredentials($configuration->getClientCredentials());
        $this->deleteToken();
    }

    private function setEnvironmentType(EnvironmentType $type): void
    {
        $this->configuration->set(self::ENVIRONMENT_TYPE, $type->value);
        $this->environment = $type->createEnvironment();
    }

    private function setClientCredentials(ClientCredentialsInterface $credentials = null): void
    {
        $this->configuration->set(self::OAUTH2_CLIENT_ID, $credentials ? $credentials->getClientId() : null);
        $this->configuration->set(self::OAUTH2_CLIENT_SECRET, $credentials ? $credentials->getClientSecret() : null);
        $this->clientCredentials = $credentials;
    }
}
