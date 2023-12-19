<?php

namespace izi\prestashop\Controller\Api;

use izi\prestashop\rest\Exception\MalformedRequestException;
use Symfony\Component\HttpFoundation\Request;

abstract class ApiController
{
    protected function decodeRequest(Request $request)
    {
        $data = json_decode($request->getContent(), false);

        if (JSON_ERROR_NONE !== json_last_error()) {
            throw MalformedRequestException::create();
        }

        return $data;
    }
}
