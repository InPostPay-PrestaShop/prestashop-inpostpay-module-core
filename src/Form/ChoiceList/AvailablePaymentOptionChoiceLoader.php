<?php

declare(strict_types=1);

namespace izi\prestashop\Form\ChoiceList;

use izi\prestashop\BasketApp\Payment\PaymentsApiClientInterface;
use izi\prestashop\Common\PaymentType;
use izi\prestashop\Form\BasketAppClientProvider;
use Symfony\Component\Form\ChoiceList\ArrayChoiceList;
use Symfony\Component\Form\ChoiceList\ChoiceListInterface;
use Symfony\Component\Form\ChoiceList\Loader\ChoiceLoaderInterface;

final class AvailablePaymentOptionChoiceLoader implements ChoiceLoaderInterface
{
    /**
     * @var BasketAppClientProvider
     */
    private $clientProvider;

    /**
     * @var PaymentsApiClientInterface|null
     */
    private $client;

    private $choices;

    public function __construct(BasketAppClientProvider $clientProvider)
    {
        $this->clientProvider = $clientProvider;
    }

    public function loadChoiceList($value = null): ChoiceListInterface
    {
        return new ArrayChoiceList($this->getChoices(), $value);
    }

    public function loadChoicesForValues(array $values, $value = null): array
    {
        return $this->loadChoiceList($value)->getChoicesForValues($values);
    }

    public function loadValuesForChoices(array $choices, $value = null): array
    {
        return $this->loadChoiceList($value)->getValuesForChoices($choices);
    }

    private function getChoices(): iterable
    {
        $client = $this->clientProvider->getClient();

        if (isset($this->choices) && $this->client === $client) {
            return $this->choices;
        }

        $this->client = $client;

        if (null === $this->client) {
            return $this->choices = PaymentType::getBankProvidedPaymentOptions();
        }

        try {
            return $this->choices = $this->client->getAvailablePaymentOptions();
        } catch (\Exception $e) {
            return $this->choices = PaymentType::getBankProvidedPaymentOptions();
        }
    }
}
