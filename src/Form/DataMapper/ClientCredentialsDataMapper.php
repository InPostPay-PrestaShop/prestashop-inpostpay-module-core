<?php

declare(strict_types=1);

namespace izi\prestashop\Form\DataMapper;

use izi\prestashop\Form\Type\MaskedPasswordType;
use izi\prestashop\OAuth2\Authentication\ClientCredentials;
use izi\prestashop\OAuth2\Authentication\ClientCredentialsInterface;
use Symfony\Component\Form\DataMapperInterface;
use Symfony\Component\Form\Exception\UnexpectedTypeException;

final class ClientCredentialsDataMapper implements DataMapperInterface
{
    public function mapDataToForms($viewData, $forms): void
    {
        if (null === $viewData) {
            return;
        }

        if (!$viewData instanceof ClientCredentialsInterface) {
            throw new UnexpectedTypeException($viewData, ClientCredentialsInterface::class);
        }

        if ($forms instanceof \Traversable) {
            $forms = iterator_to_array($forms);
        }

        $forms['clientId']->setData($viewData->getClientId());
        $forms['clientSecret']->setData($viewData->getClientSecret());
    }

    public function mapFormsToData($forms, &$viewData): void
    {
        if ($forms instanceof \Traversable) {
            $forms = iterator_to_array($forms);
        }

        $clientId = $forms['clientId']->getData();
        $clientSecret = $forms['clientSecret']->getData();

        if (MaskedPasswordType::MASKED_VALUE === $clientSecret) {
            return;
        }

        if (null === $clientId && null === $clientSecret) {
            $viewData = null;
        } else {
            $viewData = new ClientCredentials($clientId ?? '', $clientSecret ?? '');
        }
    }
}
