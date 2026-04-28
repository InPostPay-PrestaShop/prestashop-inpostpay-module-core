<?php

declare(strict_types=1);

use PrestaShop\PrestaShop\Core\Filter\FilterInterface;
use PrestaShop\PrestaShop\Core\Filter\FrontEndObject\CartFilter;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

class InpostIziCartModuleFrontController extends ModuleFrontController
{
    /**
     * @var InPostIzi
     */
    public $module;

    protected $content_only = true;

    public function postProcess(): void
    {
        $request = $this->module->getCurrentRequest();
        assert(null !== $request);

        try {
            $response = $this->handle($request);
        } catch (HttpExceptionInterface $e) {
            $response = new JsonResponse([
                'message' => $e->getMessage(),
            ], $e->getStatusCode(), $e->getHeaders());
        } catch (Throwable $e) {
            $this->module->getLogger()->critical('An error occurred while processing the cart request.', [
                'exception' => $e,
            ]);

            $response = new JsonResponse([
                'message' => 'Something went wrong, please try again later.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $this->context->cookie->write();
        $response->send();

        exit;
    }

    private function handle(Request $request): JsonResponse
    {
        if ('GET' !== $request->getMethod()) {
            $message = sprintf('No route found for "%s %s": Method Not Allowed (Allow: GET)', $request->getMethod(), $request->getPathInfo());

            throw new MethodNotAllowedHttpException(['GET'], $message);
        }

        if (0 >= $this->context->cart->id || !$this->isTokenValid()) {
            throw new AccessDeniedHttpException('Access denied.');
        }

        $cart = $this->cart_presenter->present($this->context->cart);

        return new JsonResponse([
            'cart' => $this->getCartDataFilter()->filter($cart),
        ]);
    }

    private function getCartDataFilter(): FilterInterface
    {
        /** @var CartFilter $filter */
        $filter = $this->get('prestashop.core.filter.front_end_object.cart');

        return $filter;
    }
}
