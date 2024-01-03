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
}
