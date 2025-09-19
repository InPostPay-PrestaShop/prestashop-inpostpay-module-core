<?php

declare(strict_types=1);

namespace izi\prestashop\Extension\Exception;

use Psr\Http\Client\NetworkExceptionInterface;
use Psr\Http\Message\RequestInterface;

final class NetworkException extends ExtensionServiceException implements NetworkExceptionInterface
{
    /**
     * @var RequestInterface
     */
    private $request;

    public function __construct(NetworkExceptionInterface $previous)
    {
        parent::__construct($previous->getMessage(), $previous->getCode(), $previous);
        $this->request = $previous->getRequest();
    }

    public function getRequest(): RequestInterface
    {
        return $this->request;
    }
}
