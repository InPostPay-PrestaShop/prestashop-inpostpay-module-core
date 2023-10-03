<?php

namespace izi;

class Connection extends Fetcher
{
    private $authorization;

    public function __construct()
    {
        $this->authorization = new Authorization();
        parent::__construct();
    }

    public function request($command, $type = "GET", $data = [], $withCode = false, $raw = false)
    {
        return $this->fetch(InPostIzi::getApiUrl() . "/$command", $type, $data, $withCode, $raw);
    }

    public function headers()
    {
        $headers = [
            "Authorization: Bearer {$this->authorization->getToken()}"
        ];
        return $headers;
    }
}
