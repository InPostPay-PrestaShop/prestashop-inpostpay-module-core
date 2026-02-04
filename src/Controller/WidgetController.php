<?php

declare(strict_types=1);

namespace izi\prestashop\Controller;

use izi\prestashop\BasketApp\Exception\BasketAppException;
use izi\prestashop\Command\GetBasketBindingKeyCommand;
use izi\prestashop\Command\GetOrderConfirmationUrlCommand;
use izi\prestashop\Command\GetProductWidgetCommand;
use izi\prestashop\CommandBusInterface;
use izi\prestashop\DependencyInjection\ServiceSubscriberInterface;
use izi\prestashop\Entities\BasketInterface;
use izi\prestashop\Entities\Cart;
use izi\prestashop\Handler\Result\BasketBindingKey;
use izi\prestashop\Handler\Result\ProductWidgetResult;
use izi\prestashop\Http\Exception\HttpExceptionInterface as BasketAppHttpException;
use izi\prestashop\Security\Voter\BindingWidgetVoter;
use Psr\Container\ContainerInterface;
use Psr\Http\Client\NetworkExceptionInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

final class WidgetController implements ServiceSubscriberInterface
{
    public const TRANSLATION_SOURCE = 'widgetcontroller';

    /**
     * @var \InPostIzi
     */
    private $module;

    /**
     * @var \Context
     */
    private $context;

    /**
     * @var CommandBusInterface
     */
    private $bus;

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @param \InPostIzi $module
     */
    public function __construct(\Module $module, \Context $context, CommandBusInterface $bus, ContainerInterface $container)
    {
        $this->module = $module;
        $this->context = $context;
        $this->bus = $bus;
        $this->container = $container;
    }

    public static function getSubscribedServices(): array
    {
        return [
            '?' . AuthorizationCheckerInterface::class,
        ];
    }

    public function getBindingKey(Request $request): JsonResponse
    {
        try {
            $this->denyAccessUnlessGranted(BindingWidgetVoter::VIEW, $request);

            if (!\Validate::isLoadedObject($this->context->cart)) {
                return new JsonResponse([
                    'message' => $this->module->l('Cart does not exist.', self::TRANSLATION_SOURCE),
                    'error_code' => 'CART_NOT_FOUND',
                ], 404);
            }

            $command = new GetBasketBindingKeyCommand(
                $this->createBasket(),
                $request->query->getBoolean('refresh')
            );

            /** @var BasketBindingKey $result */
            $result = $this->bus->handle($command);
            $this->context->cookie->inpostizi_basket_id = $result->getBasketId();

            return new JsonResponse($result->getBindingKey());
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    public function getOrderConfirmationUrl(): JsonResponse
    {
        try {
            $command = new GetOrderConfirmationUrlCommand((string) $this->context->cookie->inpostizi_basket_id);

            /** @var string $url */
            $url = $this->bus->handle($command);

            return new JsonResponse($url);
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    private function createBasket(): BasketInterface
    {
        if (!\Validate::isLoadedObject($this->context->cart)) {
            throw new BadRequestHttpException($this->module->l('Cart does not exist.', self::TRANSLATION_SOURCE));
        }

        return new Cart($this->context->cart);
    }

    public function getWidgetHook(string $hook, int $productId, ?int $productAttributeId = null): JsonResponse
    {
        try {
            if ('' === $hook = trim($hook)) {
                throw new BadRequestHttpException($this->module->l('Hook name is required.', self::TRANSLATION_SOURCE));
            }

            if (0 >= $productId) {
                throw new BadRequestHttpException($this->module->l('Product ID is required.', self::TRANSLATION_SOURCE));
            }

            $command = new GetProductWidgetCommand($hook, $productId, $productAttributeId);

            /** @var ProductWidgetResult $result */
            $result = $this->bus->handle($command);

            return new JsonResponse([
                'content' => $result->getContent(),
            ]);
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
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

        $this->module->getLogger()->critical('An error occurred while processing widget request.', [
            'exception' => $e,
        ]);

        return new JsonResponse([
            'message' => $this->module->l('Something went wrong. Please try again later.', self::TRANSLATION_SOURCE),
        ], 500);
    }

    /**
     * @param mixed $attributes
     * @param mixed $subject
     */
    private function denyAccessUnlessGranted($attributes, $subject = null, string $message = 'Access Denied.'): void
    {
        if ($this->isGranted($attributes, $subject)) {
            return;
        }

        throw new AccessDeniedHttpException($message);
    }

    private function isGranted($attributes, $subject = null): bool
    {
        if (null === $authChecker = $this->get(AuthorizationCheckerInterface::class)) {
            return true;
        }

        return $authChecker->isGranted($attributes, $subject);
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $name
     *
     * @return T|null
     */
    private function get(string $name)
    {
        if (!$this->container->has($name)) {
            return null;
        }

        return $this->container->get($name);
    }
}
