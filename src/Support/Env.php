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
        $value = getenv(Str::upper($key));

        if (is_string($value)) {
            return $value;
        }

        return isset(static::$env->$key) ? static::$env->$key : $default;
    }

    /**
     * Permet de modifier l'information de l'environement
     *
     * @param string $key
     * @param null $value
     */
    public static function set($key, $value)
    {
        if (isset(static::$env->$key)) {
            static::$env->$key = $value;
        }

        putenv(Str::upper($key) . '=' . $value);
    }
}