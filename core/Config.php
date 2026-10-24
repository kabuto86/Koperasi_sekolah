<?php
// Membaca config/app.php sekali sahaja.
class Config
{
    private static $values = null;

    public static function get($key, $default = null)
    {
        if (self::$values === null) {
            self::$values = require APP_ROOT . '/config/app.php';
        }

        if (array_key_exists($key, self::$values)) {
            return self::$values[$key];
        }

        return $default;
    }
}
