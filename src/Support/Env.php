<?php
namespace Bow\Support;

class Env
{
    private static $env;

    /**
     * @return bool
     */
    public static function isLoaded()
    {
        return static::$env !== null;
    }

    /**
     * @param string $filename
     */
    public static function load($filename)
    {
        if (static::$env == null) {
            static::$env = json_decode(file_get_contents($filename));
        }
    }

    /**
     * Permet de récuperer le information de l'environement
     *
     * @param string $key
     * @param null $default
     * @return mixed
     */
    public static function get($key, $default = null)
    {
        return isset(static::$env->$key) ? static::$env->$key : $default;
    }
}