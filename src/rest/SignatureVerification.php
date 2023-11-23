<?php

namespace izi\prestashop\rest;

use izi\prestashop\InpostIziPayPrestashop;
use izi\prestashop\Logger;

class SignatureVerification
{
    public function check()
    {
        Logger::log('SignatureVerification 1!');
        $authorized = false;

        $headers = \getallheaders();
        $headers = array_change_key_case($headers);
        $request_key_hash = (!empty($headers['x-public-key-hash'])) ? $headers['x-public-key-hash'] : '';
        $request_signature = (!empty($headers['x-signature'])) ? $headers['x-signature'] : '';
        $request_time = (!empty($headers['x-signature-timestamp'])) ? $headers['x-signature-timestamp'] : '';
        $request_ver = (!empty($headers['x-public-key-ver'])) ? $headers['x-public-key-ver'] : '';
        $cached_keys = $this->getSignatureKeys();
        if (!empty($cached_keys->hashes) && !in_array($request_key_hash, $cached_keys->hashes)) {
            $cached_keys = $this->getSignatureKeys(true);
        }
        if (!empty($cached_keys->hashes) && in_array($request_key_hash, $cached_keys->hashes)) {
            $body = file_get_contents('php://input');
            $request_body = (!empty($body)) ? $body : '';
            $request_body_hash = hash('sha256', $request_body, true);
            $digest = base64_encode($request_body_hash);
            $mechant_id = $cached_keys->merchant_external_id;
            $generated_signature = base64_encode("$digest,$mechant_id,$request_ver,$request_time");
            $api_key = (!empty($cached_keys->public_keys[0]->public_key_base64)) ? $cached_keys->public_keys[0]->public_key_base64 : '';

            $publicKey = "-----BEGIN PUBLIC KEY-----\n" . $api_key . "\n-----END PUBLIC KEY-----";
            $publicKeyResource = openssl_get_publickey($publicKey);

            if ($publicKeyResource !== false) {
                $verifyResult = openssl_verify($generated_signature, base64_decode($request_signature), $publicKeyResource, OPENSSL_ALGO_SHA256);
                if ($verifyResult === 1) {
                    $request_timestamp = strtotime($request_time);
                    if ($request_timestamp >= time() - 240) {
                        $authorized = true;
                    }
                }
            }
        }

        if (!$authorized) {
            http_response_code(401);
            die(json_encode(['error_code' => 'INVALID_SIGNATURE']));
        } else {
            return true;
        }
    }

    private function getSignatureKeys($force = false)
    {
        $keys = $force ? '' : \Configuration::get('izi_signing_keys_json');
        if ($keys) {
            $keys = json_decode($keys, false);
        }
        $cacheDay = \Configuration::get('izi_keyclock_token_date');
        if (!$keys || $cacheDay != date('Y-m-d')) {
            $response = InpostIziPayPrestashop::getInstance()->getController()->getSignatureKeys();

            if (!empty($response) && !empty($response->public_keys)) {
                $hashes = [];
                foreach ($response->public_keys as $key => $value) {
                    $hashes[] = hash('sha256', $value->public_key_base64);
                }
                $response->hashes = $hashes;

                \Configuration::updateValue('izi_signing_keys_json', json_encode($response));
                \Configuration::updateValue('izi_signing_keys_json_date', date('Y-m-d'));
                $keys = $response;
            }
        }

        return $keys;
    }
}
