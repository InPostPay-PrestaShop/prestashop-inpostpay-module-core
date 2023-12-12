<?php

namespace izi\prestashop;

use izi\interfaces\TokenCacheInterface;

class TokenCache implements TokenCacheInterface
{
    public function getCachedToken(): ?string
    {
        $date = \Configuration::get('izi_keyclock_token_date');
        $maxInterval = (int) \Configuration::get('izi_keyclock_token_expiration');
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

        return (string) \Configuration::get('izi_keyclock_token');
    }

    public function setCachedToken(?string $token, ?int $expiresIn)
    {
        \Configuration::updateValue('izi_keyclock_token', $token);
        \Configuration::updateValue('izi_keyclock_token_date', date('Y-m-d H:i:00'));
        \Configuration::updateValue('izi_keyclock_token_expiration', $expiresIn);
    }
}
