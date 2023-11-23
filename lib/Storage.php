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

    public static function insertSession($key, $value)
    {
        $_SESSION[$key] = $value;
    }

    public static function issetSession($key)
    {
        return isset($_SESSION[$key]);
    }

    public static function findSession($key)
    {
        if (self::issetSession($key)) {
            return $_SESSION[$key];
        }

        return null;
    }

    public static function eraseSession($key)
    {
        unset($_SESSION[$key]);
    }
}
