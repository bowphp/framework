<?php

declare(strict_types=1);

namespace Bow\Support;

class Env
{
    /**
     * The env collection
     *
     * @var array
     */
    private static bool $loaded = false;

    /**
     * Check if env is load
     *
     * @return bool
     */
    public static function isLoaded()
    {
        return static::$loaded;
    }

    /**
     * Load env file
     *
     * @param string $filename
     * @return void
     * @throws
     */
    public static function load(string $filename)
    {
        if (static::$loaded) {
            return;
        }

        if (!file_exists($filename)) {
            throw new \InvalidArgumentException(
                "The application environment file [.env.json] cannot be empty or is not define."
            );
        }

        // Get the env file content
        $content = file_get_contents($filename);

        $envs = json_decode(trim($content), true);

        foreach ($envs as $key => $value) {
            $key = Str::upper(trim($key));
            putenv($key . '=' . $value);
        }

        if (json_last_error() == JSON_ERROR_SYNTAX) {
            throw new \ErrorException(json_last_error_msg());
        }

        if (json_last_error() == JSON_ERROR_INVALID_PROPERTY_NAME) {
            throw new \ErrorException('Check environment file json syntax (.env.json)');
        }

        if (json_last_error() != JSON_ERROR_NONE) {
            throw new \ErrorException(json_last_error_msg());
        }

        static::$loaded = true;
    }

    /**
     * Retrieve information from the environment
     *
     * @param  string $key
     * @param  mixed $default
     * @return mixed
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $key = Str::upper(trim($key));

        $value = getenv($key);

        if ($value === false) {
            return $default;
        }

        return $value;
    }

    /**
     * Allows you to modify the information of the environment
     *
     * @param string $key
     * @param mixed   $value
     * @return mixed
     */
    public static function set(string $key, mixed $value): bool
    {
        $key = Str::upper(trim($key));

        return putenv($key . '=' . $value);
    }
}
