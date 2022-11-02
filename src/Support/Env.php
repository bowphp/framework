<?php

declare(strict_types=1);

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
     *
     * @return void
     * @throws
     */
    public static function load($filename)
    {
        if (static::$env != null) {
            return;
        }

        if (!file_exists($filename)) {
            throw new \InvalidArgumentException(
                "The application environment file [.env.json] cannot be empty or is not define."
            );
        }

        // Get the env file content
        $content = file_get_contents($filename);

        static::$env = json_decode(trim($content), true);

        if (json_last_error() == JSON_ERROR_SYNTAX) {
            throw new \ErrorException(json_last_error_msg());
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
     *
     * @return mixed
     */
    public static function get($key, $default = null)
    {
        $value = getenv(Str::upper($key));

        if (is_string($value)) {
            return $value;
        }

        return static::$env[$key] ?? $default;
    }

    /**
     * Allows you to modify the information of the environment
     *
     * @param string $key
     * @param null   $value
     *
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
