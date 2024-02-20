<?php

declare(strict_types=1);

namespace izi\prestashop\Validator;

use PrestaShop\PrestaShop\Core\ConstraintValidator\Constraints\DefaultLanguage;

/**
 * @Annotation
 * @Target({"PROPERTY", "ANNOTATION"})
 */
class NotBlankInDefaultLanguage extends DefaultLanguage
{
}
