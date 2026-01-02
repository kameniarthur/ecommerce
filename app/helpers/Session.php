<?php
// app/helpers/Session.php

class Session
{
    public static function start()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public static function set($key, $value)
    {
        $_SESSION[$key] = $value;
    }

    public static function get($key, $default = null)
    {
        return $_SESSION[$key] ?? $default;
    }

    public static function has($key)
    {
        return isset($_SESSION[$key]);
    }

    public static function remove($key)
    {
        unset($_SESSION[$key]);
    }

    public static function destroy()
    {
        session_destroy();
        $_SESSION = [];
    }

    public static function regenerate()
    {
        session_regenerate_id(true);
    }

    public static function flash($key, $value)
    {
        self::set("flash_{$key}", $value);
    }

    public static function getFlash($key)
    {
        $value = self::get("flash_{$key}");
        self::remove("flash_{$key}");
        return $value;
    }

    public static function all()
    {
        return $_SESSION;
    }

    // Gestion utilisateur
    public static function setUser($user)
    {
        self::set('user', $user);
    }

    public static function getUser()
    {
        return self::get('user');
    }

    public static function isLoggedIn()
    {
        return self::has('user');
    }

    public static function logout()
    {
        self::remove('user');
    }
}
