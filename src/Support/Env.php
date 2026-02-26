<?php

declare(strict_types=1);

namespace Bow\Support;

use Bow\Application\Exception\ApplicationException;
use ErrorException;
use InvalidArgumentException;

/**
 * Class Env
 *
 * @package Bow\Support
 * @method static bool  isLoaded()
 * @method static mixed get(string $key, mixed $default = null)
 * @method static bool  set(string $key, mixed $value)
 * @method static array all()
 */
class Env
{
    /**
     * The env collection
     *
     * @var bool
     */
    private static bool $loaded = false;

    /**
     * The Env instance
     *
     * @var ?Env
     */
    private static ?Env $instance = null;

    /**
     * The static envs
     *
     * @var array
     */
    private array $envs = [];

    /**
     * Env constructor.
     *
     * @throws
     */
    public function __construct(?string $filename = null)
    {
        if ($this->isLoaded()) {
            return;
        }

        if ($filename === null || !file_exists($filename)) {
            $this->envs = [];
        } else {
            $this->envs = json_decode(file_get_contents($filename), true, 512, JSON_THROW_ON_ERROR);
        }

        $this->envs = $this->bindVariables($this->envs);

        foreach ($this->envs as $key => $value) {
            $key = Str::upper(trim($key));
            putenv($key . '=' . json_encode($value));
        }

        if (json_last_error() == JSON_ERROR_SYNTAX) {
            throw new ErrorException(json_last_error_msg());
        }

        if (json_last_error() == JSON_ERROR_INVALID_PROPERTY_NAME) {
            throw new ErrorException('Check environment file json syntax (.env.json)');
        }

        if (json_last_error() != JSON_ERROR_NONE) {
            throw new ErrorException(json_last_error_msg());
        }

        static::$loaded = true;
    }

    /**
     * Load env file
     *
     * @param  string $filename
     * @return void
     * @throws
     */
    public static function configure(string $filename)
    {
        if (static::$instance !== null) {
            return;
        }

        static::$instance = new Env($filename);
    }

    /**
     * Check if env is load
     *
     * @return bool
     */
    public function isLoaded(): bool
    {
        return static::$loaded;
    }

    /**
     * Get the Env instance
     *
     * @return Env
     */
    public static function getInstance(): Env
    {
        if (!is_null(static::$instance)) {
            return static::$instance;
        }

        static::$instance = new Env();

        return static::$instance;
    }

    /**
     * Retrieve information from the environment
     *
     * @param  string $key
     * @param  mixed  $default
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $key = Str::upper(trim($key));

        $value = $this->envs[$key] ?? getenv($key);

        if ($value === false) {
            return $default;
        }

        if (!is_string($value)) {
            return $value;
        }

        $data = json_decode($value, true, 512);

        return json_last_error() ? $value : $data;
    }

    /**
     * Allows you to modify the information of the environment
     *
     * @param  string $key
     * @param  mixed  $value
     * @return mixed
     */
    public function set(string $key, mixed $value): bool
    {
        $key = Str::upper(trim($key));

        $this->envs[$key] = $value;

        return putenv($key . '=' . $value);
    }

    /**
     * Retrieve all environment information
     *
     * @return array
     */
    public function all(): array
    {
        return $this->envs;
    }

    /**
     * Bind variable
     *
     * @param  array $envs
     * @return array
     */
    private function bindVariables(array $envs): array
    {
        $keys = array_keys($this->envs);

        foreach ($envs as $env_key => $value) {
            foreach ($keys as $key) {
                if ($key == $env_key) {
                    break;
                }
                if (is_array($value)) {
                    $envs[$env_key] = $this->bindVariables($value);
                    break;
                }
                if (is_string($value) && preg_match("/\\$\{\s*$key\s*\}/", $value)) {
                    $envs[$env_key] = str_replace('${' . $key . '}', $this->envs[$key], $value);
                    break;
                }
            }
        }

        return $envs;
    }

    /**
     * Handle dynamic calls to the class methods.
     *
     * @param  string $name
     * @param  array  $arguments
     * @return mixed
     */
    public static function __callStatic($name, $arguments)
    {
        if (method_exists(static::$instance, $name)) {
            return call_user_func_array([static::$instance, $name], $arguments);
        }

        throw new \BadMethodCallException("Method {$name} does not exist.");
    }
}
