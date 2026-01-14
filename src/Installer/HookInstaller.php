<?php

declare(strict_types=1);

namespace izi\prestashop\Installer;

use izi\prestashop\Installer\Exception\InstallerException;
use izi\prestashop\Installer\Hook\HooksProviderInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class HookInstaller implements InstallerInterface
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var HooksProviderInterface
     */
    private $hooksProvider;

    public function __construct(TranslatorInterface $translator, HooksProviderInterface $hooksProvider)
    {
        $this->translator = $translator;
        $this->hooksProvider = $hooksProvider;
    }

    public function install(\Module $module): void
    {
        $hookNames = $this->hooksProvider->getHookNames();

        if ($module->registerHook($hookNames)) {
            return;
        }

        throw new InstallerException($this->translator->trans('Could not register hooks.', [], 'Modules.Inpostizi.Installer'));
    }
}
