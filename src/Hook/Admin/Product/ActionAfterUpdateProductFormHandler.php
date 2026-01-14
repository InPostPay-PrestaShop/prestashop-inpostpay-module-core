<?php

declare(strict_types=1);

namespace izi\prestashop\Hook\Admin\Product;

use izi\prestashop\CommandBusInterface;
use izi\prestashop\Hook\Exception\InvalidHookParamException;
use izi\prestashop\Hook\PrestaShopVersionAwareHookInterface;
use izi\prestashop\Hook\VersionRange;
use izi\prestashop\ProductOptions\Message\UpdateProductOptionsCommand;
use PrestaShop\PrestaShop\Core\Module\Exception\ModuleErrorException;
use Symfony\Contracts\Translation\TranslatorInterface;

final class ActionAfterUpdateProductFormHandler implements PrestaShopVersionAwareHookInterface
{
    public const HOOK_NAME = 'actionAfterUpdateProductFormHandler';

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var CommandBusInterface
     */
    private $bus;

    /**
     * @var bool
     */
    private $debug;

    public function __construct(TranslatorInterface $translator, CommandBusInterface $bus, bool $debug = false)
    {
        $this->translator = $translator;
        $this->bus = $bus;
        $this->debug = $debug;
    }

    public static function getHookName(): string
    {
        return self::HOOK_NAME;
    }

    public static function getVersionRange(): VersionRange
    {
        return new VersionRange('8.0.0');
    }

    /**
     * @param array{form_data: array|\ArrayAccess} $parameters
     */
    public function execute(array $parameters): void
    {
        $data = $parameters['form_data'] ?? null;

        if (!\is_array($data) && !$data instanceof \ArrayAccess) {
            throw InvalidHookParamException::unexpectedType('form_data', $data, 'array');
        }

        /** @var UpdateProductOptionsCommand|null $command */
        $command = $data['options'][ActionProductFormBuilderModifier::OPTIONS_FORM_NAME] ?? null;

        if (null === $command) {
            return;
        }

        try {
            $this->bus->handle($command);
        } catch (\Exception $e) {
            if ($this->debug) {
                throw $e;
            }

            throw new ModuleErrorException($this->translator->trans('An error occurred while updating InPost Pay options.', [], 'Modules.Inpostizi.Errors'), 0, $e);
        }
    }
}
