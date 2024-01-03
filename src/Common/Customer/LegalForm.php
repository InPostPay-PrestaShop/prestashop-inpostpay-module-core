<?php

declare(strict_types=1);

namespace izi\prestashop\Common\Customer;

use izi\prestashop\Enum\StringEnum;

/**
 * @method static self Person()
 * @method static self Company()
 */
final class LegalForm extends StringEnum
{
    private const PERSON = 'PERSON';
    private const COMPANY = 'COMPANY';
}
