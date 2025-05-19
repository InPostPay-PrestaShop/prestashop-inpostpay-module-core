<?php

namespace izi\prestashop\Event;

use AdminInPostConfirmedShipmentsController;
use Symfony\Component\HttpFoundation\Request;

final class CreateShipmentRequestEvent extends Event
{
    /**
     * @var AdminInPostConfirmedShipmentsController
     */
    private $controller;

    /**
     * @var Request
     */
    private $request;

    public function __construct(Request $request, AdminInPostConfirmedShipmentsController $controller)
    {
        $this->controller = $controller;
        $this->request = $request;
    }

    public function getController(): AdminInPostConfirmedShipmentsController
    {
        return $this->controller;
    }

    public function getRequest(): Request
    {
        return $this->request;
    }
}
