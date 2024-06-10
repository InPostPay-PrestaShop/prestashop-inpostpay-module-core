<?php

declare(strict_types=1);

namespace izi\prestashop\Order\Message;

use Symfony\Component\ExpressionLanguage\ExpressionLanguage as BaseExpressionLanguage;

class ExpressionLanguage extends BaseExpressionLanguage
{
    protected function registerFunctions(): void
    {
        parent::registerFunctions();

        unset($this->functions['constant']); // disallow function usage since PS defines sensitive parameters (e.g. DB password) as constants
    }
}
