<?php

declare(strict_types=1);

namespace izi\prestashop\Http\Client\Adapter;

use GuzzleHttp\Exception\ConnectException;
use Psr\Http\Client\NetworkExceptionInterface;
use Psr\Http\Message\RequestInterface;

final class NetworkException extends \RuntimeException implements NetworkExceptionInterface
{
    private $request;

    public function __construct(RequestInterface $request, string $message = '', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);

        $this->request = $request;
    }

    public function getRequest(): RequestInterface
    {
        return $this->request;
    }

    public static function fromGuzzleException(ConnectException $exception, RequestInterface $request): self
    {
        return new self($request, $exception->getMessage(), 0, $exception);
    }
}
