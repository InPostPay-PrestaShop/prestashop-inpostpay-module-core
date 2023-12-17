<?php

namespace izi;

class Storage
{
    public static function initialize()
    {
        if (session_status() !== PHP_SESSION_ACTIVE && !headers_sent()) {
            $maxlifetime = 60 * 60 * 24;
            $secure = true;
            $httponly = true;

            if (PHP_VERSION_ID < 70300) {
                session_set_cookie_params(
                    $maxlifetime,
                    '/; samesite=lax',
                    $_SERVER['HTTP_HOST'],
                    $secure,
                    $httponly);
            } else {
                session_set_cookie_params([
                    'lifetime' => $maxlifetime,
                    'path' => '/',
                    'domain' => $_SERVER['HTTP_HOST'],
                    'secure' => $secure,
                    'httponly' => $httponly,
                    'samesite' => 'lax',
                ]);
            }
            session_start();
        }
    }

    /**
     * @param mixed $value
     */
    public static function insertSession(string $key, $value)
    {
        $_SESSION[$key] = $value;
    }

    public static function issetSession(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    /**
     * @return mixed|null
     */
    public static function findSession(string $key)
    {
        if (self::issetSession($key)) {
            return $_SESSION[$key];
        }

        return null;
    }

    public static function eraseSession(string $key)
    {
        unset($_SESSION[$key]);
    }
}
