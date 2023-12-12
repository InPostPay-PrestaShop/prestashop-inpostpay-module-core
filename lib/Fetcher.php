<?php

namespace izi;

class Fetcher
{
    private $curl;

    public function __construct()
    {
    }

    public function query($url, $payload)
    {
        $this->init();

        $this->curlJoinHeaders(['content-type: application/x-www-form-urlencoded']);
        curl_setopt($this->curl, CURLOPT_POSTFIELDS, http_build_query($payload));

        return $this->execute($url);
    }

    public function fetch($url, $type = 'GET', $payload = null, $withCode = false, $raw = false)
    {
        $this->init();

        $headers = ['Content-Type:application/json'];
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, $type);
        $payloadLength = 0;
        if ($payload && ((is_array($payload) && count($payload)) || !is_array($payload))) {
            curl_setopt($this->curl, CURLOPT_POST, 1);
            $json_payload = '';
            if ($raw) {
                $json_payload = $payload;
            } else {
                $json_payload = mb_convert_encoding(json_encode($payload, JSON_UNESCAPED_SLASHES), 'UTF-8');
            }

            $payloadLength = strlen($json_payload);
            $headers[] = 'Content-length: ' . $payloadLength;
            if (method_exists(InPostIzi::getLoggerClass(), 'rawData')) {
                InPostIzi::getLoggerClass()::rawData($json_payload);
            }
            curl_setopt($this->curl, CURLOPT_POSTFIELDS, $json_payload);
        } else {
            $headers[] = 'Content-length:0';
        }

        $this->curlJoinHeaders($headers);
        $data = $this->execute($url);

        InPostIzi::getLoggerClass()::request($url, $type, json_encode($payload), $payloadLength, json_encode($data[0]), $data[1]);

        return $withCode ? $data : $data[0];
    }

    public function init()
    {
        $this->curl = curl_init();

        curl_setopt($this->curl, CURLOPT_SSLVERSION, 1);
        curl_setopt($this->curl, CURLINFO_HEADER_OUT, 1);
        curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($this->curl, CURLOPT_FOLLOWLOCATION, 1);
    }

    public function curlJoinHeaders($headers)
    {
        curl_setopt($this->curl, CURLOPT_HTTPHEADER, array_merge($this->headers(), $headers));
    }

    public function headers()
    {
        return [];
    }

    public function execute($url)
    {
        curl_setopt($this->curl, CURLOPT_URL, $url);
        curl_setopt($this->curl, CURLOPT_TIMEOUT, 10);
        $output = curl_exec($this->curl);
        $httpcode = curl_getinfo($this->curl, CURLINFO_HTTP_CODE);
        curl_close($this->curl);

        return [json_decode($output), $httpcode];
    }
}
