<?php

declare(strict_types=1);

namespace izi\prestashop\BasketApp;

use izi\prestashop\BasketApp\Basket\Request\Basket;
use izi\prestashop\BasketApp\Basket\Response\BasketBindingKeyResponse;
use izi\prestashop\BasketApp\Basket\Response\BasketBindingResponse;
use izi\prestashop\BasketApp\Basket\Response\UpdateBasketResponse;
use izi\prestashop\BasketApp\Exception\BasketAppException;
use izi\prestashop\BasketApp\Order\Request\OrderEvent;
use izi\prestashop\BasketApp\Payment\Response\AvailablePaymentOptions;
use izi\prestashop\BasketApp\Product\ProductsApiClientInterface;
use izi\prestashop\BasketApp\Product\Request\CreateProductsRequest;
use izi\prestashop\BasketApp\Product\Response\CreateProductsResponse;
use izi\prestashop\BasketApp\Product\Response\Product as ResponseProduct;
use izi\prestashop\BasketApp\Signature\Response\SigningKey;
use izi\prestashop\BasketApp\Signature\Response\SigningKeys;
use izi\prestashop\Common\Error\Error;
use izi\prestashop\Common\HotProduct\Product;
use izi\prestashop\Environment\ProductionEnvironment;
use izi\prestashop\Http\Exception\ClientException;
use izi\prestashop\Http\Exception\RedirectionException;
use izi\prestashop\Http\Exception\ServerException;
use izi\prestashop\Http\Util\UriResolver;
use izi\prestashop\Serializer\Normalizer\BasketAppPaginationPageDenormalizer;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\SerializerInterface;

final class BasketAppClient implements BasketAppClientInterface, ProductsApiClientInterface
{
    /**
     * @var ClientInterface
     */
    private $client;

    /**
     * @var RequestFactoryInterface
     */
    private $requestFactory;

    /**
     * @var StreamFactoryInterface
     */
    private $streamFactory;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var string
     */
    private $baseUri;

    public function __construct(ClientInterface $client, RequestFactoryInterface $requestFactory, StreamFactoryInterface $streamFactory, SerializerInterface $serializer, ?string $baseUri = null)
    {
        $this->client = $client;
        $this->requestFactory = $requestFactory;
        $this->streamFactory = $streamFactory;
        $this->serializer = $serializer;
        $this->baseUri = $baseUri ?? (new ProductionEnvironment())->getBasketAppApiUri();
    }

    public function updateBasket(string $basketId, Basket $basket): UpdateBasketResponse
    {
        $request = $this->createRequest('PUT', sprintf('/v2/izi/basket/%s', $basketId), $basket);
        $response = $this->sendRequest($request);

        return $this->deserialize($response, UpdateBasketResponse::class);
    }

    public function initializeBasketBinding(string $basketId): BasketBindingKeyResponse
    {
        $request = $this->createRequest('PUT', sprintf('/v2/izi/basket/%s/binding', $basketId));
        $response = $this->sendRequest($request);

        return $this->deserialize($response, BasketBindingKeyResponse::class);
    }

    public function deleteBasketBinding(string $basketId, bool $orderCompleted = false): void
    {
        $uri = sprintf('/v1/izi/basket/%s/binding', $basketId);
        if ($orderCompleted) {
            $uri .= '?' . self::buildQuery(['if_basket_realized' => (int) $orderCompleted]);
        }

        $request = $this->createRequest('DELETE', $uri);
        $this->sendRequest($request, 204);
    }

    public function getBasketBinding(string $basketId, ?string $browserId = null): BasketBindingResponse
    {
        $uri = sprintf('/v1/izi/basket/%s/binding', $basketId);
        if (null !== $browserId) {
            $uri .= '?' . self::buildQuery(['browser_id' => $browserId]);
        }

        $request = $this->createRequest('GET', $uri);
        $response = $this->sendRequest($request);

        return $this->deserialize($response, BasketBindingResponse::class);
    }

    public function updateOrder(string $orderId, OrderEvent $event): void
    {
        $request = $this->createRequest('POST', sprintf('/v1/izi/order/%s/event', $orderId), $event);
        // currently the API returns 201 on success instead of 200 given in the documentation
        $this->sendRequest($request, 200, 201);
    }

    public function getSigningKey(string $version): SigningKey
    {
        $request = $this->createRequest('GET', sprintf('/v1/izi/signing-keys/public/%s', $version));
        $response = $this->sendRequest($request);

        return $this->deserialize($response, SigningKey::class);
    }

    public function getSigningKeys(): SigningKeys
    {
        $request = $this->createRequest('GET', '/v1/izi/signing-keys/public');
        $response = $this->sendRequest($request);

        return $this->deserialize($response, SigningKeys::class);
    }

    public function getAvailablePaymentOptions(): AvailablePaymentOptions
    {
        $request = $this->createRequest('GET', '/v1/izi/payment-methods');
        $response = $this->sendRequest($request);

        return $this->deserialize($response, AvailablePaymentOptions::class);
    }

    public function createProducts(CreateProductsRequest $products): CreateProductsResponse
    {
        $request = $this->createRequest('POST', '/v1/izi/products', $products);
        $response = $this->sendRequest($request, 201);

        return $this->deserialize($response, CreateProductsResponse::class);
    }

    /**
     * @param string[] $productIds
     *
     * @return PaginationPage<ResponseProduct>
     */
    public function getProductsPage(array $productIds = [], ?int $pageSize = null, ?int $pageIndex = null): PaginationPage
    {
        $params = [];

        if (null !== $pageIndex) {
            $params['page_index'] = $pageIndex;
        }

        if (null !== $pageSize) {
            $params['page_size'] = $pageSize;
        }

        if ([] !== $productIds) {
            $params['product_ids'] = implode(',', $productIds);
        }

        $uri = '/v1/izi/products';
        if ([] !== $params) {
            $uri .= '?' . self::buildQuery($params);
        }

        $request = $this->createRequest('GET', $uri);
        $response = $this->sendRequest($request);

        return $this->deserialize($response, PaginationPage::class, [
            BasketAppPaginationPageDenormalizer::ITEM_TYPE_KEY => ResponseProduct::class,
        ]);
    }

    /**
     * @param string[] $productIds
     *
     * @return \Generator<ResponseProduct>
     */
    public function getProducts(array $productIds = [], ?int $pageSize = null): \Traversable
    {
        $pageIndex = 0;

        do {
            $page = $this->getProductsPage($productIds, $pageSize, $pageIndex++);
            $pageSize = $page->getPageSize();
            $totalCount = $page->getTotalCount();

            foreach ($page as $product) {
                yield $product;
            }
        } while ($totalCount > $pageSize * $pageIndex);
    }

    public function updateProduct(string $productId, Product $product): ResponseProduct
    {
        $request = $this->createRequest('PUT', sprintf('/v1/izi/product/%s', $productId), $product);
        $response = $this->sendRequest($request);

        return $this->deserialize($response, ResponseProduct::class);
    }

    public function deleteProduct(string $productId): void
    {
        $request = $this->createRequest('DELETE', sprintf('/v1/izi/product/%s', $productId));
        $this->sendRequest($request, 204);
    }

    private function createRequest(string $method, string $uri, $payload = null): RequestInterface
    {
        $uri = UriResolver::resolve($uri, $this->baseUri);

        $request = $this->requestFactory
            ->createRequest($method, $uri)
            ->withHeader('Accept', 'application/json');

        if (null === $payload) {
            return $request;
        }

        $payload = $this->serializer->serialize($payload, 'json', [
            'datetime_format' => self::DATETIME_FORMAT,
            'datetime_timezone' => self::DATETIME_ZONE,
        ]);

        $body = $this->streamFactory->createStream($payload);

        return $request
            ->withBody($body)
            ->withHeader('Content-Type', 'application/json');
    }

    private function sendRequest(RequestInterface $request, int $expectedStatusCode = 200, int ...$allowedStatusCodes): ResponseInterface
    {
        $response = $this->client->sendRequest($request);
        $statusCode = $response->getStatusCode();

        if (300 <= $statusCode) {
            $this->handleUnsuccessfulResponse($request, $response);
        }

        if ($expectedStatusCode === $statusCode || in_array($statusCode, $allowedStatusCodes, true)) {
            return $response;
        }

        throw new \UnexpectedValueException(sprintf('Unexpected server response code: %d', $statusCode));
    }

    /**
     * @template T
     *
     * @param class-string<T> $class
     *
     * @return T
     */
    private function deserialize(ResponseInterface $response, string $class, array $context = [])
    {
        return $this->serializer->deserialize((string) $response->getBody(), $class, 'json', $context);
    }

    private function handleUnsuccessfulResponse(RequestInterface $request, ResponseInterface $response): void
    {
        $statusCode = $response->getStatusCode();

        try {
            $error = $this->deserialize($response, Error::class);

            throw BasketAppException::create($request, $error, $statusCode); // TODO? replace with exception factory service
        } catch (ExceptionInterface $e) {
            // the response could not be deserialized into an Error, throw a generic HTTP exception instead
        }

        if (500 <= $statusCode) {
            throw new ServerException($request, $response);
        }

        if (400 <= $statusCode) {
            throw new ClientException($request, $response);
        }

        throw new RedirectionException($request, $response);
    }

    private static function buildQuery(array $params): string
    {
        return http_build_query($params, '', '&', PHP_QUERY_RFC3986);
    }
}
