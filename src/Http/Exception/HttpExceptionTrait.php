<?php

declare(strict_types=1);

namespace izi\prestashop\Http\Exception;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * @mixin \Exception
 */
trait HttpExceptionTrait
{
    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var ResponseInterface
     */
    private $response;

    public function __construct(RequestInterface $request, ResponseInterface $response)
    {
        $this->request = $request;
        $this->response = $response;

        $statusCode = $response->getStatusCode();
        $message = sprintf('HTTP %d returned for "%s".', $statusCode, $request->getUri());

        parent::__construct($message, $statusCode);
    }

    public function getRequest(): RequestInterface
    {
        return $this->request;
    }

    public function getResponse(): ResponseInterface
    {
        return $this->response;
    }
}
