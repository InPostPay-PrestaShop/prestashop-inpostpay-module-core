<?php

declare(strict_types=1);

namespace izi\prestashop\Configuration;

use izi\prestashop\Environment\EnvironmentFactory;
use izi\prestashop\Environment\EnvironmentFactoryInterface;
use izi\prestashop\Environment\EnvironmentInterface;
use izi\prestashop\Environment\EnvironmentType;
use izi\prestashop\OAuth2\Authentication\ClientCredentials;
use izi\prestashop\OAuth2\Authentication\ClientCredentialsInterface;
use izi\prestashop\OAuth2\Token\AccessTokenInterface;
use izi\prestashop\OAuth2\Token\AccessTokenRepositoryInterface;
use izi\prestashop\OAuth2\Token\BearerToken;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @implements PersistentConfigurationInterface<ApiConfigurationInterface>
 */
final class ApiConfiguration implements ApiConfigurationInterface, AccessTokenRepositoryInterface, PersistentConfigurationInterface
{
    public const OAUTH2_CLIENT_SECRET = 'INPOST_PAY_client_secret';
    public const ACCESS_TOKEN = 'INPOST_PAY_ACCESS_TOKEN';

    private const ENVIRONMENT_TYPE = 'INPOST_PAY_environment';
    private const OAUTH2_CLIENT_ID = 'INPOST_PAY_client_id';
    private const WIDGET_VERSION = 'INPOST_PAY_WIDGET_VERSION';
    private const MERCHANT_CLIENT_ID = 'INPOST_PAY_MERCHANT_CLIENT_ID';

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

    /**
     * @var EnvironmentFactoryInterface|null
     */
    private $environmentFactory;

    public function __construct(ConfigurationInterface $configuration, SerializerInterface $serializer, ?EnvironmentFactoryInterface $environmentFactory = null)
    {
        $this->configuration = $configuration;
        $this->serializer = $serializer;
        $this->environmentFactory = $environmentFactory ?? new EnvironmentFactory();
    }

    /**
     * @internal
     */
    public function getEnvironmentType(): EnvironmentType
    {
        $value = (int) $this->configuration->get(self::ENVIRONMENT_TYPE);

        return EnvironmentType::tryFrom($value) ?? EnvironmentType::Production();
    }

    public function getEnvironment(): EnvironmentInterface
    {
        return $this->environment ?? $this->environment = $this->createEnvironment();
    }

    public function getMerchantClientId(): string
    {
        return (string) $this->configuration->get(self::MERCHANT_CLIENT_ID);
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
        $configuration = new DTO\ApiConfiguration(
            $this->getEnvironmentType(),
            $this->getClientCredentials()
        );

        return $configuration->setMerchantClientId($this->getMerchantClientId());
    }

    public function persist(ApiConfigurationInterface $configuration): void
    {
        if (is_callable([$configuration, 'getMerchantClientId'])) {
            $merchantClientId = $configuration->getMerchantClientId();
        } else {
            @trigger_error(sprintf('Not implementing the "getMerchantClientId()" method in "%s" is deprecated.', get_class($configuration)), \E_USER_DEPRECATED);

            $merchantClientId = null;
        }

        $this->setEnvironment($configuration);
        $this->configuration->set(self::MERCHANT_CLIENT_ID, $merchantClientId);
        $this->setClientCredentials($configuration->getClientCredentials());
        $this->deleteToken();
    }

    private function setEnvironment(ApiConfigurationInterface $configuration): void
    {
        $type = $this->resolveEnvironmentType($configuration);
        $widgetVersion = $this->resolveWidgetVersion($configuration);

        $this->configuration->set(self::ENVIRONMENT_TYPE, $type->value);
        $this->configuration->set(self::WIDGET_VERSION, $widgetVersion);
        $this->environment = $this->createEnvironment();
    }

    private function setClientCredentials(?ClientCredentialsInterface $credentials = null): void
    {
        $this->configuration->set(self::OAUTH2_CLIENT_ID, $credentials ? $credentials->getClientId() : null);
        $this->configuration->set(self::OAUTH2_CLIENT_SECRET, $credentials ? $credentials->getClientSecret() : null);
        $this->clientCredentials = $credentials;
    }

    private function isWidgetV2(): bool
    {
        if (!$this->configuration->has(self::WIDGET_VERSION)) {
            return true;
        }

        return '2.0' === $this->configuration->get(self::WIDGET_VERSION);
    }

    private function createEnvironment(): EnvironmentInterface
    {
        $type = $this->getEnvironmentType();
        $widgetV2 = $this->isWidgetV2();

        return $this->environmentFactory->createEnvironment($type, $widgetV2);
    }

    private function resolveEnvironmentType(ApiConfigurationInterface $configuration): EnvironmentType
    {
        $environment = $configuration->getEnvironment();

        if (!is_callable([$environment, 'getType'])) {
            @trigger_error(sprintf('Not implementing the "getType()" method in "%s" is deprecated.', get_class($environment)), E_USER_DEPRECATED);

            return $configuration->getEnvironmentType();
        }

        return $environment->getType();
    }

    private function resolveWidgetVersion(ApiConfigurationInterface $configuration): string
    {
        $environment = $configuration->getEnvironment();

        if (!is_callable([$environment, 'getWidgetVersion'])) {
            return '1.0';
        }

        return '2.0';
    }
}
