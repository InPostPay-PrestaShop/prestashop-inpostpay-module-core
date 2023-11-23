<?php

namespace izi\prestashop;

class TokenCache
{
    public function getCachedToken()
    {
        $date = \Configuration::get('izi_keyclock_token_date');
        $maxInterval = intval(\Configuration::get('izi_keyclock_token_expiration'));
        if (!$date) {
            Logger::log('TOKEN: No date for token');

            return null;
        }
        if (!$maxInterval) {
            Logger::log('TOKEN: No expiration set');

            return null;
        }
        $date = strtotime($date);
        $now = time();
        $interval = $now - $date;
        if ($interval >= $maxInterval) {
            Logger::log('TOKEN: token too old');

            return null;
        }
        Logger::log('TOKEN: all good');

        return \Configuration::get('izi_keyclock_token');
    }

    public function setCachedToken($token, $expiration)
    {
        \Configuration::updateValue('izi_keyclock_token', $token);
        \Configuration::updateValue('izi_keyclock_token_date', date('Y-m-d H:i:00'));
        \Configuration::updateValue('izi_keyclock_token_expiration', $expiration);
    }
}
