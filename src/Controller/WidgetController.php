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
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

final class WidgetController implements ServiceSubscriberInterface
{
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
                    'message' => $this->trans('Cart does not exist.', [], 'Modules.Inpostizi.Errors'),
                    'error_code' => 'CART_NOT_FOUND',
                ], Response::HTTP_NOT_FOUND);
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
            throw new BadRequestHttpException($this->trans('Cart does not exist.', [], 'Modules.Inpostizi.Errors'));
        }

        return new Cart($this->context->cart);
    }

    public function getWidgetHook(string $hook, int $productId, ?int $productAttributeId = null): JsonResponse
    {
        try {
            if ('' === $hook = trim($hook)) {
                throw new BadRequestHttpException($this->trans('Hook name is required.', [], 'Modules.Inpostizi.Errors'));
            }

            if (0 >= $productId) {
                throw new BadRequestHttpException($this->trans('Product ID is required.', [], 'Modules.Inpostizi.Errors'));
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
                'message' => $this->trans('There was a problem communicating with the mobile application. Please try again later.', [], 'Modules.Inpostizi.Errors'),
            ], Response::HTTP_BAD_GATEWAY);
        }

        if ($e instanceof \DomainException) {
            return new JsonResponse([
                'message' => $this->trans('Your request could not be processed.', [], 'Modules.Inpostizi.Errors'),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $this->module->getLogger()->critical('An error occurred while processing widget request.', [
            'exception' => $e,
        ]);

        return new JsonResponse([
            'message' => $this->trans('Something went wrong. Please try again later.', [], 'Modules.Inpostizi.Errors'),
        ], Response::HTTP_INTERNAL_SERVER_ERROR);
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

    private function trans(string $message, array $parameters, string $domain): string
    {
        return $this->context->getTranslator()->trans($message, $parameters, $domain);
    }
}
