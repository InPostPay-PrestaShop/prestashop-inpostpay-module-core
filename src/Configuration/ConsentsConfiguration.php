<?php

declare(strict_types=1);

namespace izi\prestashop\Configuration;

use izi\prestashop\Command\Config\UpdateConsentsConfigurationCommand;
use izi\prestashop\Configuration\DTO\Consent;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\SerializerInterface;

final class ConsentsConfiguration implements ConsentsConfigurationInterface, PersistentConfigurationInterface
{
    private const CONSENTS = 'INPOST_PAY_CONSENTS';

    /**
     * @var ShopAwareConfigurationInterface
     */
    private $configuration;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    private $consents;

    public function __construct(ShopAwareConfigurationInterface $configuration, SerializerInterface $serializer)
    {
        $this->configuration = $configuration;
        $this->serializer = $serializer;
    }

    public function getConsents(?int $shopId = null): array
    {
        if (!isset($this->consents[(int) $shopId])) {
            $this->consents[(int) $shopId] = $this->loadConsents($shopId);
        }

        return array_map(static function (Consent $consent): Consent {
            return clone $consent;
        }, $this->consents[(int) $shopId]);
    }

    /**
     * @return Consent[]
     */
    private function loadConsents(?int $shopId): array
    {
        $config = $this->configuration->get(self::CONSENTS, $shopId);

        if (null === $config) {
            return [];
        }

        try {
            return $this->serializer->deserialize($config, Consent::class . '[]', 'json');
        } catch (ExceptionInterface $e) {
            return [];
        }
    }

    public function copy(): ConsentsConfigurationInterface
    {
        return new UpdateConsentsConfigurationCommand(...$this->getConsents());
    }

    public function persist(ConsentsConfigurationInterface $configuration): void
    {
        $consents = $configuration->getConsents();

        $value = $this->serializer->serialize($consents, 'json');
        $this->configuration->set(self::CONSENTS, $value);

        $this->consents[0] = array_map(static function (Consent $consent): Consent {
            return clone $consent;
        }, $consents);
    }
}
