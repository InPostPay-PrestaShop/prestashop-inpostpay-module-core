<?php

declare(strict_types=1);

namespace izi\prestashop\BasketApp\Exception;

use izi\prestashop\Common\Error\Error;
use Psr\Http\Message\RequestInterface;

class BasketAppException extends \RuntimeException
{
    private const CLASS_MAP = [
        BadRequestException::ERROR_CODE => BadRequestException::class,
        MalformedRequestException::ERROR_CODE => MalformedRequestException::class,
        UnauthorizedException::ERROR_CODE => UnauthorizedException::class,
        ForbiddenException::ERROR_CODE => ForbiddenException::class,
        ResourceNotFoundException::ERROR_CODE => ResourceNotFoundException::class,
        BasketNotFoundException::ERROR_CODE => BasketNotFoundException::class,
        BrowserNotFoundException::ERROR_CODE => BrowserNotFoundException::class,
        PublicKeyNotFoundException::ERROR_CODE => PublicKeyNotFoundException::class,
        OrderNotFoundException::ERROR_CODE => OrderNotFoundException::class,
        MerchantDisabledException::ERROR_CODE => MerchantDisabledException::class,
        BasketAlreadyBoundException::ERROR_CODE => BasketAlreadyBoundException::class,
        PhoneBindingUnavailableException::ERROR_CODE => PhoneBindingUnavailableException::class,
        BasketNotBoundException::ERROR_CODE => BasketNotBoundException::class,
        BasketExpiredException::ERROR_CODE => BasketExpiredException::class,
        CannotChangeOrderStatusException::ERROR_CODE => CannotChangeOrderStatusException::class,
        InternalServerErrorException::ERROR_CODE => InternalServerErrorException::class,
    ];

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var Error
     */
    private $error;

    public function __construct(RequestInterface $request, Error $error, int $statusCode)
    {
        $this->request = $request;
        $this->error = $error;

        parent::__construct($error->getMessage(), $statusCode);
    }

    public static function create(RequestInterface $request, Error $error, int $statusCode): self
    {
        $class = self::CLASS_MAP[$error->getCode()] ?? self::class;

        return new $class($request, $error, $statusCode);
    }

    public function getRequest(): RequestInterface
    {
        return $this->request;
    }

    public function getError(): Error
    {
        return $this->error;
    }
}
