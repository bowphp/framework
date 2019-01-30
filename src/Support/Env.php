<?php

namespace Bow\Support;

class Env
{
    /**
     * The env collection
     *
     * @var object
     */
    private static $env;

    /**
     * Check if env is load
     *
     * @return bool
     */
    public static function isLoaded()
    {
        return static::$env !== null;
    }

    /**
     * Load env file
     *
     * @param string $filename
     * @throws
     */
    public static function load($filename)
    {
        if (static::$env != null) {
            return;
        }

        static::$env = json_decode(trim(file_get_contents($filename)), true);

        if (json_last_error() == JSON_ERROR_SYNTAX) {
            throw new \ErrorException('');
        }

        if (json_last_error() == JSON_ERROR_INVALID_PROPERTY_NAME) {
            throw new \ErrorException('Check environment file json syntax (.env.json)');
        }

        if (json_last_error() != JSON_ERROR_NONE) {
            throw new \ErrorException(json_last_error_msg());
        }
    }

    /**
     * Retrieve information from the environment
     *
     * @param  string $key
     * @param  null   $default
     * @return mixed
     */
    public static function get($key, $default = null)
    {
        $value = getenv(Str::upper($key));

        if (is_string($value)) {
            return $value;
        }

        return isset(static::$env[$key]) ? static::$env[$key] : $default;
    }

    /**
     * Allows you to modify the information of the environment
     *
     * @param string $key
     * @param null   $value
     * @return mixed
     */
    public static function set($key, $value)
    {
        if (isset(static::$env->$key)) {
            return static::$env->$key = $value;
        }

        return putenv(Str::upper($key) . '=' . $value);
    }
}
