<?php

declare(strict_types=1);

namespace izi\prestashop\Hook\Legacy;

use izi\prestashop\Module\Exception\PrestaShopModuleErrorException;
use Symfony\Component\Form\FormInterface;

final class ControllerHelper
{
    private function __construct()
    {
    }

    /**
     * @see \AdminControllerCore::postProcess()
     */
    public static function setFormErrors(\AdminControllerCore $controller, FormInterface $form): void
    {
        $errors = self::getFormErrors($form);
        $error = array_pop($errors);
        $controller->errors = array_merge($controller->errors, $errors);
        $controller->setRedirectAfter(null);

        throw new PrestaShopModuleErrorException($error);
    }

    /**
     * @return string[]
     */
    private static function getFormErrors(FormInterface $form): array
    {
        $errors = [];

        foreach ($form->getErrors(true) as $error) {
            if (null === $origin = $error->getOrigin()) {
                $errors[] = $error->getMessage();
            } else {
                $label = $origin->getConfig()->getOption('label') ?? $origin->getName();
                $errors[] = sprintf('%s: %s', $label, $error->getMessage());
            }
        }

        return $errors;
    }
}
