<?php

use izi\prestashop\Controller\Api\BasketController;
use izi\prestashop\Controller\Api\OrderController;
use izi\prestashop\Controller\MerchantController;
use izi\prestashop\rest\Exception\ApiException;
use izi\prestashop\rest\Exception\InternalServerErrorException;
use izi\prestashop\rest\SignatureVerification;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class InpostIziBackendModuleFrontController extends ModuleFrontController
{
    private const MERCHANT_ROUTES = [
        [
            'path' => '/inpost/v1/izi/merchant/basket/get/link',
            'controller' => [MerchantController::class, 'getLink'],
        ],
        [
            'path' => '/inpost/v1/izi/merchant/basket/confirmation',
            'controller' => [MerchantController::class, 'checkBindingConfirmation'],
        ],
        [
            'path' => '/inpost/v1/izi/merchant/basket/delete/binding',
            'controller' => [MerchantController::class, 'deleteBinding'],
        ],
        [
            'path' => '/inpost/v1/izi/merchant/order/confirmation/get',
            'controller' => [MerchantController::class, 'checkOrderConfirmation'],
        ],
        [
            'path' => '/inpost/v1/izi/merchant/basket/post/binding/{prefix}/{number}',
            'prefix' => '/inpost/v1/izi/merchant/basket/post/binding',
            'regex' => '#^/inpost/v1/izi/merchant/basket/post/binding(?:/(?<prefix>.+?)(?:/(?<number>.+?))?)?$#',
            'controller' => [MerchantController::class, 'bindCart'],
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
    ];

    protected $content_only = true;

    public function postProcess()
    {
        $request = Request::createFromGlobals();

        $response = $this->handle($request);
        $response->send();

        exit;
    }

    private function handle(Request $request): Response
    {
        $path = $this->getPath($request);

        try {
            if (0 === strpos($path, '/inpost/v1/izi/merchant/')) {
                return $this->handleCustomerRequest($request, $path);
            }

            return $this->handleApiRequest($request, $path);
        } catch (\Throwable $throwable) {
            $this->logError($throwable);

            throw $throwable;
        }
    }

    private function handleCustomerRequest(Request $request, $path): Response
    {
        [$controller, $params] = $this->resolveController($request, $path, self::MERCHANT_ROUTES);

        return null === $controller
            ? $this->createNotFoundResponse($request, $path)
            : $this->callController($controller, $request, $params);
    }

    private function handleApiRequest(Request $request, string $path): Response
    {
        $method = $request->getMethod();

        \izi\prestashop\Logger::response($request->getContent(), sprintf('Request: [%s %s]     URI: %s', $method, $path, $request->server->get('REQUEST_URI', $path)));

        try {
            (new SignatureVerification())->check($request);
            [$controller, $params] = $this->resolveController($request, $path, self::API_ROUTES);

            /** @var JsonResponse $response */
            $response = null === $controller
                ? $this->createNotFoundResponse($request, $path, true)
                : $this->callController($controller, $request, $params);
        } catch (\Throwable $throwable) {
            $response = $this->handleApiError($throwable);
        }

        \izi\prestashop\Logger::response($response->getContent(), sprintf('Response: [%s %s]', $method, $path));

        return $response->setEncodingOptions(JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JsonResponse::DEFAULT_ENCODING_OPTIONS);
    }

    private function handleApiError(\Throwable $throwable): Response
    {
        if (!$throwable instanceof ApiException) {
            $throwable = InternalServerErrorException::create($throwable);
        }

        if ($throwable instanceof InternalServerErrorException && $previous = $throwable->getPrevious()) {
            $this->logError($previous);
        }

        return new JsonResponse([
            'error_code' => $throwable->getErrorCode(),
            'error_message' => $throwable->getMessage(),
        ], $throwable->getStatusCode());
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

    private function logError(\Throwable $throwable): void
    {
        \izi\prestashop\Logger::log($throwable->getMessage() . ' at ' . $throwable->getFile() . ':' . $throwable->getLine());
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
        $controller = [new $controller[0](), $controller[1]];

        return $controller(...$arguments);
    }

    private function resolveControllerArguments(array $controller, Request $request, array $pathParams): array
    {
        $reflection = new \ReflectionMethod($controller[0], $controller[1]);

        return array_map(function (\ReflectionParameter $param) use ($request, $pathParams) {
            return $this->resolveControllerArgument($param, $request, $pathParams);
        }, $reflection->getParameters());
    }

    private function resolveControllerArgument(\ReflectionParameter $param, Request $request, array $pathParams)
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

        throw new \LogicException(sprintf('Cannot determine controller parameter value for argument "%s".', $paramName));
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
}
