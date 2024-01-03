<?php

declare(strict_types=1);

namespace izi\prestashop\Cache\Exception;

use Psr\SimpleCache\CacheException;

final class RuntimeException extends \RuntimeException implements CacheException
{
}
