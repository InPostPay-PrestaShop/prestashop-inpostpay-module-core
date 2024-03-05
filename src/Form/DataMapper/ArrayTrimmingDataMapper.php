<?php

declare(strict_types=1);

namespace izi\prestashop\Form\DataMapper;

use Symfony\Component\Form\DataMapperInterface;
use Symfony\Component\Form\Exception\UnexpectedTypeException;

/**
 * Removes non-submitted elements from array data.
 */
final class ArrayTrimmingDataMapper implements DataMapperInterface
{
    public function mapDataToForms($viewData, $forms): void
    {
        if (null === $viewData) {
            return;
        }

        if (!is_array($viewData)) {
            throw new UnexpectedTypeException($viewData, 'array');
        }

        foreach ($forms as $name => $form) {
            $form->setData($viewData[$name] ?? null);
        }
    }

    public function mapFormsToData($forms, &$viewData): void
    {
        $viewData = [];

        foreach ($forms as $name => $form) {
            $viewData[$name] = $form->getData();
        }
    }
}
