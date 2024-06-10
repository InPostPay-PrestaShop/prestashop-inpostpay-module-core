<?php

use izi\prestashop\BasketApp\Exception\BasketAppException;
use izi\prestashop\Controller\Api\BasketController;
use izi\prestashop\Controller\Api\OrderController;
use izi\prestashop\Controller\WidgetController;
use izi\prestashop\Http\Exception\HttpExceptionInterface;
use izi\prestashop\Http\Exception\ServerException;
use izi\prestashop\MerchantApi\Exception\ApiException;
use izi\prestashop\MerchantApi\Exception\BadGatewayException;
use izi\prestashop\MerchantApi\Exception\InternalServerErrorException;
use izi\prestashop\MerchantApi\Exception\ServiceUnavailableException;
use izi\prestashop\MerchantApi\Firewall\MerchantApiAuthenticator;
use izi\prestashop\OAuth2\Exception\OAuth2ExceptionInterface;
use Psr\Http\Client\NetworkExceptionInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Symfony\Component\Debug\ErrorHandler as LegacyErrorHandler;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\ErrorHandler\ErrorHandler;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class InpostIziBackendModuleFrontController extends ModuleFrontController
{
    private const MERCHANT_ROUTES = [
        [
            'path' => '/inpost/v1/izi/merchant/basket/get/link',
            'controller' => [WidgetController::class, 'getDeepLink'],
        ],
        [
            'path' => '/inpost/v1/izi/merchant/basket/confirmation',
            'controller' => [WidgetController::class, 'getIsBound'],
        ],
        [
            'path' => '/inpost/v1/izi/merchant/basket/delete/binding',
            'methods' => ['DELETE'],
            'controller' => [WidgetController::class, 'deleteBinding'],
        ],
        [
            'path' => '/inpost/v1/izi/merchant/order/confirmation/get',
            'controller' => [WidgetController::class, 'getOrderComplete'],
        ],
        [
            'path' => '/inpost/v1/izi/merchant/widget/get/{hook}/{productId}',
            'prefix' => '/inpost/v1/izi/merchant/widget/get',
            'regex' => '#^/inpost/v1/izi/merchant/widget/get/(?<hook>.+)/(?<productId>\d+)$#',
            'controller' => [WidgetController::class, 'getWidgetHook'],
        ],
        [
            'path' => '/inpost/v1/izi/merchant/basket/post/binding/{prefix}/{number}',
            'prefix' => '/inpost/v1/izi/merchant/basket/post/binding',
            'regex' => '#^/inpost/v1/izi/merchant/basket/post/binding(?:/(?<prefix>.+?)(?:/(?<number>.+?))?)?$#',
            'controller' => [WidgetController::class, 'getPayData'],
        ],
    ];

    private const API_ROUTES = [
        [
            'path' => '/inpost/v1/izi/order',
            'methods' => ['POST'],
            'controller' => [OrderController::class, 'create'],
        ],
        [
            'path' => '/inpost/v1/izi/order/{orderId}',
            'methods' => ['GET'],
            'prefix' => '/inpost/v1/izi/order/',
            'regex' => '#^/inpost/v1/izi/order/(?<orderId>\d+)$#',
            'controller' => [OrderController::class, 'get'],
        ],
        [
            'path' => '/inpost/v1/izi/order/{orderId}/event',
            'methods' => ['POST'],
            'prefix' => '/inpost/v1/izi/order/',
            'regex' => '#^/inpost/v1/izi/order/(?<orderId>\d+)/event$#',
            'controller' => [OrderController::class, 'update'],
        ],
        [
            'path' => '/inpost/v1/izi/basket/{basketId}',
            'methods' => ['GET'],
            'prefix' => '/inpost/v1/izi/basket/',
            'regex' => '#^/inpost/v1/izi/basket/(?<basketId>.+)$#',
            'controller' => [BasketController::class, 'get'],
        ],
        [
            'path' => '/inpost/v1/izi/basket/{basketId}/confirmation',
            'methods' => ['POST'],
            'prefix' => '/inpost/v1/izi/basket/',
            'regex' => '#^/inpost/v1/izi/basket/(?<basketId>.+)/confirmation$#',
            'controller' => [BasketController::class, 'confirm'],
        ],
        [
            'path' => '/inpost/v1/izi/basket/{basketId}/event',
            'methods' => ['POST'],
            'prefix' => '/inpost/v1/izi/basket/',
            'regex' => '#^/inpost/v1/izi/basket/(?<basketId>.+)/event$#',
            'controller' => [BasketController::class, 'update'],
        ],
        [
            'path' => '/inpost/v1/izi/basket/{basketId}/binding',
            'methods' => ['DELETE'],
            'prefix' => '/inpost/v1/izi/basket/',
            'regex' => '#^/inpost/v1/izi/basket/(?<basketId>.+)/binding$#',
            'controller' => [BasketController::class, 'deleteBinding'],
        ],
        [
            'path' => '/inpost/v1/izi/basket/{basketId}/binding/delete',
            'methods' => ['POST'],
            'prefix' => '/inpost/v1/izi/basket/',
            'regex' => '#^/inpost/v1/izi/basket/(?<basketId>.+)/binding/delete$#',
            'controller' => [BasketController::class, 'deleteBinding'],
        ],
    ];

    /**
     * @var InPostIzi
     */
    public $module;

    protected $content_only = true;

    public function postProcess()
    {
        $this->registerErrorHandler();

        $request = $this->module->getCurrentRequest();

        $response = $this->handle($request);
        $response->send();

        exit;
    }

    /**
     * @param Country $defaultCountry
     */
    protected function geolocationManagement($defaultCountry): bool
    {
        return false;
    }

    protected function displayRestrictedCountryPage(): void
    {
    }

    // TODO use own Sf Kernel?
    private function handle(Request $request): Response
    {
        $path = $this->getPath($request);

        try {
            if (0 !== strpos($path, '/inpost/v1/izi/merchant/')) {
                return $this->handleApiRequest($request, $path);
            }

            $response = $this->handleCustomerRequest($request, $path);
            $response->prepare($request);

            $this->context->cookie->write();

            return $response;
        } catch (Throwable $throwable) {
            http_response_code(500);
            $this->logError($throwable);

            throw $throwable;
        }
    }

    private function handleCustomerRequest(Request $request, string $path): Response
    {
        [$controller, $params] = $this->resolveController($request, $path, self::MERCHANT_ROUTES);

        return null === $controller
            ? $this->createNotFoundResponse($request, $path)
            : $this->callController($controller, $request, $params);
    }

    private function handleApiRequest(Request $request, string $path): Response
    {
        $method = $request->getMethod();

        /** @var LoggerInterface $logger */
        $logger = $this->module->get('inpost.izi.merchant_api_logger');
        $logger->info('Request "{method} {path}"', [
            'method' => $method,
            'path' => $path,
        ]);

        if ($body = $request->getContent()) {
            $logger->debug('Request body: "{body}"', ['body' => $body]);
        }

        try {
            $this->module->get(MerchantApiAuthenticator::class)->authenticate($request);
            [$controller, $params] = $this->resolveController($request, $path, self::API_ROUTES);

            /** @var JsonResponse $response */
            $response = null === $controller
                ? $this->createNotFoundResponse($request, $path, true)
                : $this->callController($controller, $request, $params);
        } catch (Throwable $throwable) {
            $response = $this->handleApiError($throwable);
        }

        $logger->info('Response "{method} {path}": {status_code}', [
            'method' => $method,
            'path' => $path,
            'status_code' => $response->getStatusCode(),
        ]);

        if (!$response->isSuccessful()) {
            $logger->error('Response body: "{body}"', ['body' => $response->getContent()]);
        } elseif ($body = $response->getContent()) {
            $logger->debug('Response body: "{body}"', ['body' => $body]);
        }

        return $response;
    }

    private function handleApiError(Throwable $throwable): Response
    {
        $exception = $this->convertApiError($throwable);

        if ($exception instanceof InternalServerErrorException && $previous = $exception->getPrevious()) {
            $this->logError($previous);
        }

        return new JsonResponse([
            'error_code' => $exception->getErrorCode(),
            'error_message' => $exception->getMessage(),
        ], $exception->getStatusCode());
    }

    private function convertApiError(Throwable $throwable): ApiException
    {
        if ($throwable instanceof ApiException) {
            return $throwable;
        }

        if ($throwable instanceof NetworkExceptionInterface) {
            $message = $throwable instanceof OAuth2ExceptionInterface
                ? 'Could not connect to the authorization server'
                : 'Could not connect to the basket app API';

            return BadGatewayException::create($throwable, $message);
        }

        if ($throwable instanceof OAuth2ExceptionInterface) {
            return BadGatewayException::create($throwable, 'Could not obtain access token');
        }

        if ($throwable instanceof BasketAppException) {
            return BadGatewayException::create($throwable, sprintf('Basket app API error: "%s"', $throwable->getError()->getCode()));
        }

        if ($throwable instanceof ServerException && 503 === $throwable->getCode()) {
            return ServiceUnavailableException::create($throwable, 'Basket app API is unavailable');
        }

        if ($throwable instanceof HttpExceptionInterface) {
            return BadGatewayException::create($throwable, sprintf('Unexpected basket app API response status code: %d', $throwable->getCode()));
        }

        return InternalServerErrorException::create($throwable);
    }

    private function getPath(Request $request): string
    {
        if (null === $path = $request->query->get('path')) {
            return '/';
        }

        $path = rawurldecode($path);

        if ('/' !== $path[0]) {
            $path = '/' . $path;
        }

        return rtrim($path, '/');
    }

    private function logError(Throwable $throwable): void
    {
        $this->module->getLogger()->error('Error processing request: {error}', ['error' => $throwable]);
    }

    private function resolveController(Request $request, string $path, array $routes): array
    {
        $method = $request->getMethod();

        foreach ($routes as $route) {
            if (isset($route['methods']) && !in_array($method, $route['methods'], true)) {
                continue;
            }

            if (isset($route['prefix']) && 0 !== strpos($path, $route['prefix'])) {
                continue;
            }

            if (!isset($route['regex']) && $path !== $route['path']) {
                continue;
            }

            if (isset($route['regex']) && !preg_match($route['regex'], $path, $params)) {
                continue;
            }

            return [$route['controller'], $params ?? []];
        }

        return [null, []];
    }

    private function callController(array $controller, Request $request, array $pathParams): Response
    {
        $arguments = $this->resolveControllerArguments($controller, $request, $pathParams);
        $controller = [$this->createController($controller[0]), $controller[1]];

        return $controller(...$arguments);
    }

    private function createController(string $className)
    {
        try {
            return $this->module->get($className);
        } catch (ServiceNotFoundException $e) {
            return new $className();
        }
    }

    private function resolveControllerArguments(array $controller, Request $request, array $pathParams): array
    {
        $reflection = new ReflectionMethod($controller[0], $controller[1]);

        return array_map(function (ReflectionParameter $param) use ($request, $pathParams) {
            return $this->resolveControllerArgument($param, $request, $pathParams);
        }, $reflection->getParameters());
    }

    private function resolveControllerArgument(ReflectionParameter $param, Request $request, array $pathParams)
    {
        $type = $param->getType();

        if (null !== $type && Request::class === $type->getName()) {
            return $request;
        }

        $paramName = $param->getName();

        if (isset($pathParams[$paramName])) {
            return $pathParams[$paramName];
        }

        if ($param->isDefaultValueAvailable()) {
            return $param->getDefaultValue();
        }

        throw new LogicException(sprintf('Cannot determine controller parameter value for argument "%s".', $paramName));
    }

    private function createNotFoundResponse(Request $request, string $path, bool $json = false): Response
    {
        $message = sprintf('No route found for "%s %s"', $request->getMethod(), $path);

        return $json || in_array('application/json', $request->getAcceptableContentTypes(), true)
            ? new JsonResponse([
                'error_code' => 'NOT_FOUND',
                'error_message' => $message,
            ], 404)
            : new Response($message, 404);
    }

    private function registerErrorHandler(): void
    {
        $handler = class_exists(ErrorHandler::class)
            ? ErrorHandler::register()
            : LegacyErrorHandler::register();

        $handler->throwAt(E_ERROR, true);
        $handler->setDefaultLogger($this->module->getLogger(), [
            E_DEPRECATED => LogLevel::DEBUG,
            E_NOTICE => LogLevel::DEBUG,
            E_STRICT => LogLevel::DEBUG,
            E_WARNING => LogLevel::DEBUG,
            E_USER_WARNING => LogLevel::DEBUG,
            E_USER_ERROR => LogLevel::CRITICAL,
            E_RECOVERABLE_ERROR => LogLevel::CRITICAL,
            E_ERROR => LogLevel::CRITICAL,
        ]);
    }
}
