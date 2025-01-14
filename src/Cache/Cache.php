<?php

declare(strict_types=1);

namespace Bow\Cache;

use BadMethodCallException;
use Bow\Cache\Adapter\RedisAdapter;
use Bow\Cache\Adapter\FilesystemAdapter;
use Bow\Cache\Adapter\CacheAdapterInterface;
use Bow\Cache\Adapter\DatabaseAdapter;
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
        "database" => DatabaseAdapter::class,
    ];

    /**
     * Cache configuration method
     *
     * @param array $config
     */
    public static function configure(array $config)
    {
        if (!is_null(static::$instance)) {
            return static::$instance;
        }

        if (!isset($config["default"])) {
            throw new InvalidArgumentException("Default store is not define");
        }

        static::$config = $config;
        $store = (array) $config["stores"][$config["default"]];

        return static::store($store["driver"]);
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
    public static function store(string $store): CacheAdapterInterface
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
     * Add the custom adapters
     *
     * @param array $adapters
     * @return void
     */
    public static function addAdapters(array $adapters): void
    {
        foreach ($adapters as $name => $adapter) {
            static::$adapters[$name] = $adapter;
        }
    }

    /**
     * __call
     *
     * @param string $name
     * @param array $arguments
     * @return mixed
     * @throws BadMethodCallException
     * @throws ErrorException
     */
    public static function __callStatic(string $name, array $arguments)
    {
        if (is_null(static::$instance)) {
            throw new ErrorException(
                "Unable to get cache instance before configuration"
            );
        }

        if (method_exists(static::$instance, $name)) {
            return call_user_func_array([static::$instance, $name], $arguments);
        }

        throw new BadMethodCallException(
            "The $name method does not exist"
        );
    }
}
