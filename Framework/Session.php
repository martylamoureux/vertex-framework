<?php


namespace Vertex\Framework;


class Session
{

    public static $expirationDays = 30;

    public static function has($name)
    {
        if (array_key_exists($name, $_SESSION))
            return true;
        return array_key_exists($name, $_COOKIE);
    }

    public static function get($name)
    {
        if (array_key_exists($name, $_SESSION))
            return $_SESSION[$name];
        if (array_key_exists($name, $_COOKIE))
            return $_COOKIE[$name];
        return NULL;
    }

    public static function temp($name, $value)
    {
        $_SESSION[$name] = $value;
    }

    public static function store($name, $value)
    {
        setcookie($name, $value, time() + 60 * 60 * 24 * static::$expirationDays);
    }

    public static function update()
    {
        foreach ($_COOKIE as $name => $value)
            setcookie($name, $value, time() + 60 * 60 * 24 * static::$expirationDays);
    }

    public static function delete($name)
    {
        if (array_key_exists($name, $_SESSION))
            unset($_SESSION[$name]);

        if (array_key_exists($name, $_COOKIE)) {
            unset($_COOKIE[$name]);
            setcookie($name, '', time() - 3600);
        }
    }

} 