<?php

declare(strict_types=1);

namespace Bow\Cache;

use BadMethodCallException;
use Bow\Cache\Adapter\RedisAdapter;
use Bow\Cache\Adapter\FilesystemAdapter;
use Bow\Cache\Adapter\CacheAdapterInterface;
use ErrorException;
use InvalidArgumentException;

class Cache
{
    /**
     * The meta data
     *
     * @var ?CacheAdapterInterface
     */
    private static ?CacheAdapterInterface $instance = null;

    /**
     * Define the config
     *
     * @var array
     */
    private static array $config;

    /**
     * Define the list of available drivers
     *
     * @var array
     */
    private static array $adapters = [
        "file" => FilesystemAdapter::class,
        "redis" => RedisAdapter::class,
    ];

    /**
     * Cache configuration method
     *
     * @param string $base_directory
     */
    public static function confirgure(array $config)
    {
        if (!is_null(static::$instance)) {
            return static::$instance;
        }

        if (!isset($config["default"])) {
            throw new InvalidArgumentException("Default store is not define");
        }

        static::$config = $config;
        $store = (array) $config["stores"][$config["default"]];

        return static::cache($store["driver"]);
    }

    /**
     * Get the cache instance
     *
     * @return CacheAdapterInterface
     */
    public static function getInstance(): CacheAdapterInterface
    {
        if (is_null(static::$instance)) {
            throw new ErrorException("Unable to get cache instance before configuration");
        }

        return static::$instance;
    }

    /**
     * Get the cache instance
     *
     * @param string $driver
     * @return CacheAdapterInterface
     */
    public static function cache(string $store): CacheAdapterInterface
    {
        $stores = static::$config["stores"];

        if (!isset($stores[$store])) {
            throw new InvalidArgumentException("The $store store is not define");
        }

        $config = $stores[$store];
        static::$instance = new static::$adapters[$config["driver"]]($config);

        return static::$instance;
    }

    /**
     * __call
     *
     * @param string $name
     * @param array $arguments
     * @return mixed
     * @throws BadMethodCallException
     */
    public static function __callStatic(string $name, array $arguments)
    {
        if (method_exists(static::$instance, $name)) {
            return call_user_func_array([static::$instance, $name], $arguments);
        }

        throw new BadMethodCallException("The $name method does not exist");
    }
}
