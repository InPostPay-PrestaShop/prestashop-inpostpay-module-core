<?php

declare(strict_types=1);

namespace izi\prestashop\Handler\Config\Status;

use izi\prestashop\Configuration\ApiConfigurationInterface;
use izi\prestashop\Configuration\OrdersConfiguration;
use izi\prestashop\Configuration\OrdersConfigurationInterface;
use izi\prestashop\Validator\InPostApiCredentials;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class ConfigurationStatusChecker implements StatusCheckerInterface
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var OrdersConfiguration
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
    public function __construct(TranslatorInterface $translator, OrdersConfigurationInterface $ordersConfiguration, ApiConfigurationInterface $apiConfiguration, ValidatorInterface $validator)
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

        if (0 !== \count($violations)) {
            yield $this->translator->trans('Configuration is incomplete - review and submit the form in the "{tab_name}" tab.', [
                '{tab_name}' => $this->translator->trans('Settings', [], 'Admin.Global'),
            ], 'Modules.Inpostizi.Status');

            return;
        }

        if (null === $this->apiConfiguration->getClientCredentials()) {
            yield $this->translator->trans('API access credentials are missing.', [], 'Modules.Inpostizi.Status');
        } else {
            $violations = $this->validator->validate($this->apiConfiguration, new InPostApiCredentials());

            /** @var ConstraintViolationInterface $violation */
            foreach ($violations as $violation) {
                yield $this->translator->trans('API access problem: {error}', [
                    '{error}' => $violation->getMessage(),
                ], 'Modules.Inpostizi.Status');
            }
        }

        if (null === $this->apiConfiguration->getMerchantClientId()) {
            yield $this->translator->trans('Merchant client ID configuration is missing. The InPost Pay Widget will not be displayed.', [], 'Modules.Inpostizi.Status');
        }
    }
}
