<?php

declare(strict_types=1);

namespace izi\prestashop\Extension\Event;

use izi\prestashop\Event\Event;

final class ExtensionEvent extends Event
{
    public const INSTALLED = 'inpostizi.extension.installed';
    public const UNINSTALLED = 'inpostizi.extension.uninstalled';
    public const UPGRADED = 'inpostizi.extension.upgraded';
    public const ENABLED = 'inpostizi.extension.enabled';
    public const DISABLED = 'inpostizi.extension.disabled';

    /**
     * @var \Module
     */
    private $module;

    public function __construct(\Module $module)
    {
        $this->module = $module;
    }

    public function getModule(): \Module
    {
        return $this->module;
    }
}
