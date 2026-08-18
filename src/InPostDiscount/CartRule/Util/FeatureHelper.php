<?php

declare(strict_types=1);

namespace izi\prestashop\InPostDiscount\CartRule\Util;

use izi\prestashop\Hook\Common\CartRule\ActionApplyCartRule;

/**
 * @final
 */
class FeatureHelper
{
    /**
     * @var \Module
     */
    private $module;

    private $customCartRulesAvailable;

    public function __construct(\Module $module)
    {
        $this->module = $module;
    }

    public function isCustomCartRulesFeatureAvailable(): bool
    {
        return $this->customCartRulesAvailable ?? $this->customCartRulesAvailable = $this->module->isRegisteredInHook(ActionApplyCartRule::HOOK_NAME);
    }
}
