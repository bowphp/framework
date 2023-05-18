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
     * Define the env list
     *
     * @var array
     */
    private static array $envs = [];

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

        static::$envs = json_decode(trim($content), true);

        static::$envs = static::bindVariables(static::$envs);

        dd(static::$envs);

        foreach (static::$envs as $key => $value) {
            $key = Str::upper(trim($key));
            putenv($key . '=' . json_encode($value));
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
        $value = static::$envs[$key] ?? getenv($key);

        if ($value === false) {
            return $default;
        }

        if (!is_string($value)) {
            return $value;
        }

        $data = json_decode($value);

        return json_last_error() ? $value : $data;
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

        static::$envs[$key] = $value;

        return putenv($key . '=' . $value);
    }

    /**
     * Bind variable
     *
     * @param array $envs
     * @return array
     */
    private static function bindVariables(array $envs): array
    {
        $keys = array_keys(static::$envs);

        foreach ($envs as $env_key => $value) {
            foreach ($keys as $key) {
                if ($key == $env_key) {
                    break;
                }
                if (is_array($value)) {
                    $envs[$env_key] = static::bindVariables($value);
                    break;
                }
                if (is_string($value) && preg_match("/\\$\{\s*$key\s*\}/", $value)) {
                    $envs[$env_key] = str_replace('${' . $key . '}', static::$envs[$key], $value);
                    break;
                }
            }
        }

        return $envs;
    }
}
