<?php

declare(strict_types=1);

namespace izi\prestashop\Http\Exception;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

interface HttpExceptionInterface
{
    public function getRequest(): RequestInterface;

    public function getResponse(): ResponseInterface;
}
