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
    private static ?array $envs = null;

    /**
     * Check if env is load
     *
     * @return bool
     */
    public static function isLoaded()
    {
        return static::$envs !== null;
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
        if (static::$envs != null) {
            return;
        }

        if (!file_exists($filename)) {
            throw new \InvalidArgumentException(
                "The application environment file [.env.json] cannot be empty or is not define."
            );
        }

        // Get the env file content
        $content = file_get_contents($filename);

        static::$envs = json_decode(trim($content), true);

        foreach (static::$envs as $key => $value) {
            putenv(Str::upper($key) . '=' . $value);
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
        $value = getenv(Str::upper($key));

        if (is_string($value)) {
            return $value;
        }

        return static::$envs[$key] ?? $default;
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
        if (isset(static::$envs[$key])) {
            static::$envs[$key] = $value;
            return true;
        }

        return putenv(Str::upper(trim($key)) . '=' . $value);
    }
}
