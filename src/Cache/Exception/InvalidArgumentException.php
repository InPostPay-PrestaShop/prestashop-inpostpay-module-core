<?php

declare(strict_types=1);

namespace izi\prestashop\Cache\Exception;

use Psr\SimpleCache\InvalidArgumentException as InvalidArgumentExceptionInterface;

final class InvalidArgumentException extends \InvalidArgumentException implements InvalidArgumentExceptionInterface
{
}
