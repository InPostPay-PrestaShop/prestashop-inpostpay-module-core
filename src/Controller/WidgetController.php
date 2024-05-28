<?php

declare(strict_types=1);

namespace izi\prestashop\Controller;

use izi\prestashop\BasketApp\Basket\Request\Browser;
use izi\prestashop\BasketApp\Exception\BasketAppException;
use izi\prestashop\Command\BindBasketCommand;
use izi\prestashop\Command\GenerateDeepLinkCommand;
use izi\prestashop\Command\GetBindingConfirmationCommand;
use izi\prestashop\Command\GetOrderEventsCommand;
use izi\prestashop\Command\GetProductWidgetCommand;
use izi\prestashop\Command\UnbindBasketCommand;
use izi\prestashop\CommandBusInterface;
use izi\prestashop\Common\BindingPlace;
use izi\prestashop\Common\Currency;
use izi\prestashop\Common\PhoneNumber;
use izi\prestashop\Entities\BasketInterface;
use izi\prestashop\Entities\Cart;
use izi\prestashop\Handler\Result\BasketBindingResult;
use izi\prestashop\Handler\Result\BindingConfirmationStream;
use izi\prestashop\Handler\Result\DeepLink;
use izi\prestashop\Handler\Result\OrderEventStream;
use izi\prestashop\Handler\Result\ProductWidgetResult;
use izi\prestashop\Http\Exception\HttpExceptionInterface as BasketAppHttpException;
use izi\prestashop\Http\Response\EventStreamResponse;
use Psr\Clock\ClockInterface;
use Psr\Http\Client\NetworkExceptionInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

final class WidgetController
{
    public const TRANSLATION_SOURCE = 'widgetcontroller';

    /**
     * @var \Module
     */
    private $module;

    /**
     * @var \Context
     */
    private $context;

    /**
     * @var ClockInterface
     */
    private $clock;

    /**
     * @var DenormalizerInterface
     */
    private $denormalizer;

    /**
     * @var CommandBusInterface
     */
    private $bus;

    public function __construct(\Module $module, \Context $context, ClockInterface $clock, DenormalizerInterface $denormalizer, CommandBusInterface $bus)
    {
        $this->module = $module;
        $this->context = $context;
        $this->clock = $clock;
        $this->denormalizer = $denormalizer;
        $this->bus = $bus;
    }

    public function getDeepLink(): JsonResponse
    {
        try {
            $command = new GenerateDeepLinkCommand($this->createBasket()->getId());

            /** @var DeepLink $deepLink */
            $deepLink = $this->bus->handle($command);

            return new JsonResponse($deepLink);
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    public function getPayData(Request $request, ?string $prefix = null, ?string $number = null): Response
    {
        try {
            $command = $this->createBindingCommand($request, $prefix, $number);

            /** @var BasketBindingResult $result */
            $result = $this->bus->handle($command);
            $this->context->cookie->inpostizi_basket_id = $result->getBasketId();

            return new JsonResponse($result->getData());
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    public function getOrderComplete(): EventStreamResponse
    {
        $command = new GetOrderEventsCommand((string) $this->context->cookie->inpostizi_basket_id);

        /** @var OrderEventStream $stream */
        $stream = $this->bus->handle($command);

        return new EventStreamResponse(static function () use ($stream) {
            foreach ($stream->getEvents() as $event) {
                echo $event;
            }
        });
    }

    public function getIsBound(): EventStreamResponse
    {
        $command = new GetBindingConfirmationCommand($this->context->cart->id ?? null);

        /** @var BindingConfirmationStream $stream */
        $stream = $this->bus->handle($command);

        if (null !== $basketId = $stream->getBasketId()) {
            $this->context->cookie->inpostizi_basket_id = $basketId;
        }

        return new EventStreamResponse(static function () use ($stream) {
            foreach ($stream->getEvents() as $event) {
                echo $event;
            }
        });
    }

    public function deleteBinding(): JsonResponse
    {
        try {
            $command = new UnbindBasketCommand($this->createBasket()->getId());

            $this->bus->handle($command);
            unset($this->context->cookie->inpostizi_basket_id);

            return JsonResponse::create(null, 204)->setContent(null);
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    public function getWidgetHook(string $hook, int $productId): JsonResponse
    {
        if ('' === $hook = trim($hook)) {
            throw new BadRequestHttpException($this->module->l('Hook name is required.', self::TRANSLATION_SOURCE));
        }

        try {
            $command = new GetProductWidgetCommand($hook, $productId);

            /** @var ProductWidgetResult $result */
            $result = $this->bus->handle($command);

            return new JsonResponse([
                'content' => $result->getContent()
            ]);
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    private function createBindingCommand(Request $request, ?string $prefix, ?string $number): BindBasketCommand
    {
        if (null === Currency::tryFrom($this->context->currency->iso_code ?? null)) {
            throw new BadRequestHttpException($this->module->l('The currently selected currency is not supported.', self::TRANSLATION_SOURCE));
        }

        $basket = $this->createBasket();

        if ([] === $this->context->cart->getProducts()) {
            throw new BadRequestHttpException($this->module->l('There are no products in your cart.', self::TRANSLATION_SOURCE));
        }

        return new BindBasketCommand(
            $basket,
            $this->getBrowserData($request),
            $this->createPhoneNumber($prefix, $number),
            $request->cookies->get('BrowserId'),
            $this->getBindingPlace($request)
        );
    }

    private function createBasket(): BasketInterface
    {
        if (!\Validate::isLoadedObject($this->context->cart)) {
            throw new BadRequestHttpException($this->module->l('Cart does not exist.', self::TRANSLATION_SOURCE));
        }

        return new Cart($this->context->cart);
    }

    private function getBrowserData(Request $request): Browser
    {
        if (!$request->query->has('browser')) {
            throw new BadRequestHttpException($this->module->l('Browser data is missing.', self::TRANSLATION_SOURCE));
        }

        if (false === $browser = base64_decode($request->query->get('browser'))) {
            throw new BadRequestHttpException($this->module->l('Could not decode browser data.', self::TRANSLATION_SOURCE));
        }

        $browser = json_decode($browser, true);
        if (null === $browser && JSON_ERROR_NONE !== json_last_error()) {
            throw new BadRequestHttpException($this->module->l('Could not decode browser data.', self::TRANSLATION_SOURCE));
        }

        if (!is_array($browser)) {
            throw new BadRequestHttpException($this->module->l('Malformed browser data.', self::TRANSLATION_SOURCE));
        }

        $browser = array_merge($browser, [
            'data_time' => $this->clock->now()->format(\DateTime::RFC3339),
            'customer_ip' => $request->server->get('REMOTE_ADDR'),
            'port' => $request->server->get('SERVER_PORT'),
        ]);

        try {
            return $this->denormalizer->denormalize($browser, Browser::class);
        } catch (ExceptionInterface $e) {
            throw new BadRequestHttpException($this->module->l('Malformed browser data.', self::TRANSLATION_SOURCE), $e);
        }
    }

    private function createPhoneNumber(?string $prefix, ?string $number): ?PhoneNumber
    {
        if (null === $prefix && null === $number) {
            return null;
        }

        if ('' === $prefix = trim($prefix)) {
            throw new BadRequestHttpException($this->module->l('Phone number prefix is required.', self::TRANSLATION_SOURCE));
        }

        if ('' === $number = trim($number)) {
            throw new BadRequestHttpException($this->module->l('Phone number is required.', self::TRANSLATION_SOURCE));
        }

        return new PhoneNumber('+' . ltrim($prefix, '+'), $number);
    }

    private function getBindingPlace(Request $request): ?BindingPlace
    {
        if (null === $bindingPlace = $request->query->get('binding_place')) {
            return null;
        }

        if (null === $bindingPlace = BindingPlace::tryFrom($bindingPlace)) {
            throw new BadRequestHttpException($this->module->l('Invalid binding place.', self::TRANSLATION_SOURCE));
        }

        return $bindingPlace;
    }

    private function handleException(\Exception $e): JsonResponse
    {
        if ($e instanceof HttpExceptionInterface) {
            return new JsonResponse([
                'message' => $e->getMessage(),
            ], $e->getStatusCode(), $e->getHeaders());
        }

        if ($e instanceof NetworkExceptionInterface || $e instanceof BasketAppException || $e instanceof BasketAppHttpException) {
            return new JsonResponse([
                'message' => $this->module->l('There was a problem communicating with the mobile application. Please try again later.', self::TRANSLATION_SOURCE),
            ], 502);
        }

        if ($e instanceof \DomainException) {
            return new JsonResponse([
                'message' => $this->module->l('Your request could not be processed.', self::TRANSLATION_SOURCE),
            ], 422);
        }

        return new JsonResponse([
            'message' => $this->module->l('Something went wrong. Please try again later.', self::TRANSLATION_SOURCE),
        ], 500);
    }
}
