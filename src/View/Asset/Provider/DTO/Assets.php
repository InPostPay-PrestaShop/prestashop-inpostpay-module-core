<?php

declare(strict_types=1);

namespace izi\prestashop\View\Asset\Provider\DTO;

class Assets
{
    /**
     * @var array<string, array>
     */
    private $javaScripts = [];

    /**
     * @var array<string, array>
     */
    private $styleSheets = [];

    /**
     * @var array<string, mixed>
     */
    private $jsVariables = [];

    /**
     * @return array<string, array> options by path
     */
    public function getJavaScripts(): array
    {
        return $this->javaScripts;
    }

    public function addJavaScript(string $path, array $options = []): self
    {
        $this->javaScripts[$path] = $options;

        return $this;
    }

    public function removeJavaScript(string $path): self
    {
        unset($this->javaScripts[$path]);

        return $this;
    }

    /**
     * @return array<string, array> options by path
     */
    public function getStyleSheets(): array
    {
        return $this->styleSheets;
    }

    public function addStyleSheet(string $path, array $options = []): self
    {
        $this->styleSheets[$path] = $options;

        return $this;
    }

    /**
     * @return array<string, mixed> values by name
     */
    public function getJavaScriptVariables(): array
    {
        return $this->jsVariables;
    }

    public function addJavaScriptVariable(string $name, $value): self
    {
        $this->jsVariables[$name] = $value;

        return $this;
    }
}
