<?php

declare(strict_types=1);

namespace izi\prestashop\Installer;

use izi\prestashop\CommandBusInterface;
use izi\prestashop\Installer\Exception\InstallerException;
use izi\prestashop\Translation\Message\ImportTranslationsCommand;
use Symfony\Contracts\Translation\TranslatorInterface;

final class TranslationInstaller implements InstallerInterface
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var CommandBusInterface
     */
    private $bus;

    /**
     * @var string
     */
    private $psVersion;

    public function __construct(TranslatorInterface $translator, CommandBusInterface $bus, string $psVersion = _PS_VERSION_)
    {
        $this->translator = $translator;
        $this->bus = $bus;
        $this->psVersion = $psVersion;
    }

    public function install(\Module $module): void
    {
        if (\Tools::version_compare($this->psVersion, '1.7.8', '>=')) {
            return;
        }

        $command = new ImportTranslationsCommand($module->getLocalPath() . 'translations');

        try {
            $this->bus->handle($command);
        } catch (\Exception $e) {
            throw new InstallerException($this->translator->trans('Could not import the module\'s translations.', [], 'Modules.Inpostizi.Installer'), 0, $e);
        }
    }
}
