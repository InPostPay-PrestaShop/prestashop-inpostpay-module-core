<?php

namespace izi;

class Authorization extends Fetcher
{
    private $token;
    private $expiration;

    public function getToken()
    {
        if (!isset($this->token)) {
            $this->token = InPostIzi::getCachedToken();
            if (!$this->token) {
                $this->login();
                InPostIzi::setCachedToken($this->token, $this->expiration);
            }
        }
        return $this->token;
    }

    public function login()
    {
        $url = InPostIzi::getAuthUrl() . '/auth/realms/external/protocol/openid-connect/token';
        $resonse = $this->query($url, [
            "client_id" => InPostIzi::getClientId(),
            "client_secret" => InPostIzi::getClientSecret(),
            "grant_type" => "client_credentials"
        ]);

        $this->token = isset($resonse, $resonse[0], $resonse[0]->access_token) ? $resonse[0]->access_token : '';
        $this->expiration = isset($resonse, $resonse[0], $resonse[0]->expires_in) ? $resonse[0]->expires_in : '';
    }

    public function headers()
    {
        return [];
    }

}
