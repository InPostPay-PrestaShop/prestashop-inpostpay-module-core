<?php

declare(strict_types=1);

namespace izi\prestashop\Handler\Config\Status;

use izi\prestashop\Configuration\ApiConfigurationInterface;
use izi\prestashop\Configuration\OrdersConfiguration;
use izi\prestashop\Configuration\OrdersConfigurationInterface;
use izi\prestashop\Translation\LegacyTranslator;
use izi\prestashop\Validator\InPostApiCredentials;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class ConfigurationStatusChecker implements StatusCheckerInterface
{
    private const TRANSLATION_SOURCE = 'configurationstatuschecker';

    /**
     * @var LegacyTranslator
     */
    private $translator;

    /**
     * @var OrdersConfigurationInterface
     */
    private $ordersConfiguration;

    /**
     * @var ApiConfigurationInterface
     */
    private $apiConfiguration;

    /**
     * @var ValidatorInterface
     */
    private $validator;

    /**
     * @param OrdersConfiguration $ordersConfiguration
     */
    public function __construct(LegacyTranslator $translator, OrdersConfigurationInterface $ordersConfiguration, ApiConfigurationInterface $apiConfiguration, ValidatorInterface $validator)
    {
        $this->translator = $translator;
        $this->ordersConfiguration = $ordersConfiguration;
        $this->apiConfiguration = $apiConfiguration;
        $this->validator = $validator;
    }

    /**
     * {@inheritDoc}
     */
    public function checkStatus(): array
    {
        return iterator_to_array($this->getErrors());
    }

    private function getErrors(): \Generator
    {
        $violations = $this->validator->validate($this->ordersConfiguration->copy());

        if (0 !== count($violations)) {
            yield $this->translator->l('Configuration is incomplete - review and submit the form in the general settings tab.', self::TRANSLATION_SOURCE);

            return;
        }

        if (!$this->ordersConfiguration->isCarrierPaymentEnabled() && !$this->ordersConfiguration->isBankPaymentEnabled()) {
            yield $this->translator->l('No payment option is enabled - update the configuration via the general settings tab.', self::TRANSLATION_SOURCE);
        }

        if (null === $this->apiConfiguration->getClientCredentials()) {
            yield $this->translator->l('API access credentials are missing.', self::TRANSLATION_SOURCE);
        } else {
            $violations = $this->validator->validate($this->apiConfiguration, new InPostApiCredentials());

            /** @var ConstraintViolationInterface $violation */
            foreach ($violations as $violation) {
                yield sprintf($this->translator->l('API access problem: %s', self::TRANSLATION_SOURCE), $violation->getMessage());
            }
        }
    }
}
