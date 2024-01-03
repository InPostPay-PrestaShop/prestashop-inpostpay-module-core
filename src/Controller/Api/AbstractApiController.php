<?php

declare(strict_types=1);

namespace izi\prestashop\Controller\Api;

use izi\prestashop\BasketApp\BasketAppClientInterface;
use izi\prestashop\CommandBusInterface;
use izi\prestashop\MerchantApi\Exception\BadRequestException;
use izi\prestashop\MerchantApi\Exception\MalformedRequestException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Exception\UnexpectedValueException;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;

abstract class AbstractApiController
{
    /**
     * @var SerializerInterface
     */
    protected $serializer;

    /**
     * @var CommandBusInterface
     */
    protected $bus;

    public function __construct(Serializer $serializer, CommandBusInterface $bus)
    {
        $this->serializer = $serializer;
        $this->bus = $bus;
    }

    /**
     * @template T
     *
     * @param class-string<T> $class
     *
     * @return T
     */
    protected function decodeRequest(Request $request, string $class)
    {
        try {
            return $this->serializer->deserialize($request->getContent(), $class, 'json', [
                'datetime_format' => BasketAppClientInterface::DATETIME_FORMAT,
            ]);
        } catch (UnexpectedValueException $e) {
            throw new MalformedRequestException('Could not decode request.', 0, $e);
        } catch (ExceptionInterface $e) {
            throw new BadRequestException('Could not decode request.', 0, $e);
        }
    }
}
