<?php

declare(strict_types=1);

namespace izi\prestashop\OAuth2\Exception;

use Psr\Http\Client\NetworkExceptionInterface;
use Psr\Http\Message\RequestInterface;

final class NetworkException extends \RuntimeException implements NetworkExceptionInterface, OAuth2ExceptionInterface
{
    private $request;

    public function __construct(NetworkExceptionInterface $previous)
    {
        $this->request = $previous->getRequest();

        parent::__construct($previous->getMessage(), $previous->getCode(), $previous);
    }

    public function getRequest(): RequestInterface
    {
        return $this->request;
    }
}
