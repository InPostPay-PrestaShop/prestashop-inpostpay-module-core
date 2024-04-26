<?php

declare(strict_types=1);

namespace izi\prestashop\View\Templating;

final class SmartyRenderer implements RendererInterface
{
    private $smarty;

    public function __construct(\Smarty $smarty)
    {
        $this->smarty = $smarty;
    }

    public function render(string $name, array $parameters = []): string
    {
        $isAdmin = $this->adjustModuleResourcePluginConfig();

        try {
            return $this->doRender($name, $parameters);
        } finally {
            $this->restoreModuleResourcePluginConfig($isAdmin);
        }
    }

    private function doRender(string $name, array $parameters)
    {
        if ([] !== $parameters) {
            $scope = $this->smarty->createData($this->smarty);
            $scope->assign($parameters);
        } else {
            $scope = $this->smarty;
        }

        return $this->smarty
            ->createTemplate($name, null, null, $scope)
            ->fetch();
    }

    private function adjustModuleResourcePluginConfig(): ?bool
    {
        if (null === $plugin = $this->getModuleResourcePlugin()) {
            $this->smarty->registered_resources['module'] = new \SmartyResourceModule([
                'modules' => _PS_MODULE_DIR_,
            ]);

            return null;
        }

        if (!isset($plugin->isAdmin)) {
            return null;
        }

        $isAdmin = $plugin->isAdmin;
        $plugin->isAdmin = false;

        return $isAdmin;
    }

    private function restoreModuleResourcePluginConfig(?bool $isAdmin): void
    {
        if (null === $isAdmin) {
            return;
        }

        $this->getModuleResourcePlugin()->isAdmin = $isAdmin;
    }

    private function getModuleResourcePlugin(): ?\SmartyResourceModule
    {
        return $this->smarty->registered_resources['module'] ?? null;
    }
}
