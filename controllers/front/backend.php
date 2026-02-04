<?php

use izi\prestashop\BasketApp\Exception\BasketAppException;
use izi\prestashop\Controller\Api\BasketController;
use izi\prestashop\Controller\Api\OrderController;
use izi\prestashop\Controller\Api\ProductController;
use izi\prestashop\Controller\WidgetController;
use izi\prestashop\Http\Client\ModuleVersionInfoProvidingClient;
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
            'path' => '/inpost/v2/izi/merchant/basket/binding-key',
            'methods' => ['GET'],
            'controller' => [WidgetController::class, 'getBindingKey'],
        ],
        [
            'path' => '/inpost/v2/izi/merchant/order/confirmation-url',
            'methods' => ['GET'],
            'controller' => [WidgetController::class, 'getOrderConfirmationUrl'],
        ],
        [
            'path' => '/inpost/v2/izi/merchant/widget/get/{hook}/{productId}/{productAttributeId}',
            'methods' => ['GET'],
            'prefix' => '/inpost/v2/izi/merchant/widget/get/',
            'regex' => '#^/inpost/v2/izi/merchant/widget/get/(?<hook>\w+)/(?<productId>\d+)(?:/(?<productAttributeId>\d+))?$#',
            'controller' => [WidgetController::class, 'getWidgetHook'],
        ],
    ];

    private const API_ROUTES = [
        [
            'path' => '/inpost/v1/izi/products',
            'methods' => ['GET'],
            'controller' => [ProductController::class, 'getProducts'],
        ],
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
        [
            'path' => '/inpost/v1/izi/basket/product/{productId}',
            'methods' => ['POST'],
            'prefix' => '/inpost/v1/izi/basket/product/',
            'regex' => '#^/inpost/v1/izi/basket/product/(?<productId>.+)$#',
            'controller' => [BasketController::class, 'addProduct'],
        ],
    ];

    /**
     * @var InPostIzi
     */
    public $module;

    protected $content_only = true;

    private $outputBuffer = '';

    public function postProcess()
    {
        $this->registerErrorHandler();
        ob_start([$this, 'handleOutput'], 1);

        $request = $this->module->getCurrentRequest();
        $request->attributes->set('_inpost_izi_shop_id', (int) $this->context->shop->id);

        $response = $this->handle($request);
        Response::closeOutputBuffers(0, false);
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
            if (!str_contains($path, '/merchant/')) {
                return $this->handleApiRequest($request, $path);
            }

            $response = $this->handleCustomerRequest($request, $path);
            $response->prepare($request);

            $this->context->cookie->write();

            return $response;
        } catch (Throwable $e) {
            http_response_code(500);
            $this->logError($e);

            throw $e;
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
        } catch (Throwable $e) {
            $response = $this->handleApiError($e);
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

        $response->headers->set(ModuleVersionInfoProvidingClient::HEADER_NAME, $this->module->version);

        return $response;
    }

    private function handleApiError(Throwable $e): Response
    {
        $apiException = $this->convertApiError($e);

        if ($apiException instanceof InternalServerErrorException && $previous = $apiException->getPrevious()) {
            $this->logError($previous);
        }

        return new JsonResponse([
            'error_code' => $apiException->getErrorCode(),
            'error_message' => $apiException->getMessage(),
        ], $apiException->getStatusCode());
    }

    private function convertApiError(Throwable $e): ApiException
    {
        if ($e instanceof ApiException) {
            return $e;
        }

        if ($e instanceof NetworkExceptionInterface) {
            $message = $e instanceof OAuth2ExceptionInterface
                ? 'Could not connect to the authorization server'
                : 'Could not connect to the basket app API';

            return BadGatewayException::create($e, $message);
        }

        if ($e instanceof OAuth2ExceptionInterface) {
            return BadGatewayException::create($e, 'Could not obtain access token');
        }

        if ($e instanceof BasketAppException) {
            return BadGatewayException::create($e, sprintf('Basket app API error: "%s"', $e->getError()->getCode()));
        }

        if ($e instanceof ServerException && 503 === $e->getCode()) {
            return ServiceUnavailableException::create($e, 'Basket app API is unavailable');
        }

        if ($e instanceof HttpExceptionInterface) {
            return BadGatewayException::create($e, sprintf('Unexpected basket app API response status code: %d', $e->getCode()));
        }

        return InternalServerErrorException::create($e);
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

    private function logError(Throwable $e): void
    {
        $this->module->getLogger()->critical('An error occurred while processing request.', ['exception' => $e]);
    }

    private function resolveController(Request $request, string $path, array $routes): array
    {
        $method = $request->getMethod();

        foreach ($routes as $route) {
            if (isset($route['methods']) && !in_array($method, $route['methods'], true)) {
                continue;
            }

            if (isset($route['prefix']) && !str_starts_with($path, $route['prefix'])) {
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
            E_USER_DEPRECATED => LogLevel::DEBUG,
            E_NOTICE => LogLevel::DEBUG,
            E_USER_NOTICE => LogLevel::DEBUG,
            E_STRICT => LogLevel::DEBUG,
            E_WARNING => LogLevel::DEBUG,
            E_USER_WARNING => LogLevel::DEBUG,
            E_USER_ERROR => LogLevel::CRITICAL,
            E_RECOVERABLE_ERROR => LogLevel::CRITICAL,
            E_ERROR => LogLevel::CRITICAL,
        ]);
    }

    private function handleOutput(string $buffer, int $phase): string
    {
        if (PHP_OUTPUT_HANDLER_FINAL & $phase && '' !== $this->outputBuffer) {
            $this->module->getLogger()->warning('Output buffer content: "{buffer}".', [
                'buffer' => $this->outputBuffer,
            ]);

            $this->outputBuffer = '';
        }

        if ('' === $buffer) {
            return '';
        }

        $this->outputBuffer .= $buffer;

        if (PHP_OUTPUT_HANDLER_START & $phase) {
            $this->module->getLogger()->warning(sprintf(
                "Output started before sending response.\n[stacktrace]\n%s\n",
                (new \Exception())->getTraceAsString()
            ));
        }

        return $buffer;
    }
}
