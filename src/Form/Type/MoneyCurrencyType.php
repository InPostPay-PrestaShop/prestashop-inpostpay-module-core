<?php

declare(strict_types=1);

namespace izi\prestashop\Form\Type;

use izi\prestashop\Form\Provider\DefaultCurrencyProvider;
use izi\prestashop\Form\Provider\DefaultCurrencyProviderInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;

final class MoneyCurrencyType extends AbstractType
{
    /**
     * @var DefaultCurrencyProviderInterface
     */
    private $currencyProvider;

    /**
     * @param DefaultCurrencyProvider $currencyProvider
     */
    public function __construct(DefaultCurrencyProviderInterface $currencyProvider)
    {
        $this->currencyProvider = $currencyProvider;
    }

    public function getParent(): string
    {
        return MoneyType::class;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $currency = $this->currencyProvider->getDefaultCurrency();

        $resolver->setDefaults([
            'currency' => $currency->iso_code ?? 'PLN',
        ]);
    }
}
