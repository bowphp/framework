<?php

namespace Bow\Database;

use Redis as RedisClient;

/**
 * @method mixed get(string $key, mixed $default = null)
 * @method mixed set(string $key, mixed $data, ?int $time = null)
 * @method Redis getClient()
 */
class Redis
{
    /**
     * Define the php-redis instance
     *
     * @var RedisClient
     */
    private static RedisClient $redis;

    /**
     * Define the instance of Redis
     *
     * @var Redis|null
     */
    private static ?Redis $instance = null;

    /**
     * RedisAdapter constructor.
     *
     * @param array $config
     * @return mixed
     */
    public function __construct(array $config)
    {
        $options = [];
        $auth = [];

        if (isset($config["password"])) {
            $auth[] = $config["password"];
        }

        if (isset($config["username"])) {
            array_unshift($auth, $config["username"]);
        }

        if (count($auth) > 0) {
            $options = compact('auth');
        }

        $options['backoff'] = [
            'algorithm' => RedisClient::BACKOFF_ALGORITHM_DECORRELATED_JITTER,
            'base' => 500,
            'cap' => 750,
        ];

        static::$redis = new RedisClient();

        static::$redis->connect(
            $config["host"],
            $config["port"] ?? 6379,
            $config["timeout"] ?? 2.5,
            null,
            0,
            0,
            $options
        );

        static::$redis->setOption(RedisClient::OPT_SERIALIZER, RedisClient::SERIALIZER_JSON);

        if (isset($config["prefix"])) {
            static::$redis->setOption(RedisClient::OPT_PREFIX, $config["prefix"]);
        }

        static::$redis->select($config["database"] ?? 0);
    }

    /**
     * Ping the redis service
     *
     * @param ?string $message
     */
    public static function ping(?string $message = null): void
    {
        static::$redis->ping($message);
    }

    /**
     * Set value on Redis
     *
     * @param string $key
     * @param mixed $data
     * @param integer|null $time
     * @return boolean
     */
    public static function set(string $key, mixed $data, ?int $time = null): bool
    {
        if (is_null(static::$instance)) {
            static::$instance = static::getInstance();
        }

        $options = [];

        if (is_callable($data)) {
            $content = $data();
        } else {
            $content = $data;
        }

        if (!is_null($time)) {
            $options = [
                'EX' => $time
            ];
        }

        return static::$redis->set($key, $content, $options);
    }

    /**
     * Get the Redis Store instance
     *
     * @return Redis
     */
    public static function getInstance(): Redis
    {
        if (is_null(static::$instance)) {
            static::$instance = new Redis(config("database.redis"));
        }

        return static::$instance;
    }

    /**
     * Get the value from Redis
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        if (is_null(static::$instance)) {
            static::$instance = static::getInstance();
        }

        if (!static::$redis->exists($key)) {
            return is_callable($default) ? $default() : $default;
        }

        $value = static::$redis->get($key);

        return is_null($value) ? $default : $value;
    }

    /**
     * Get the php-redis client
     *
     * @see https://github.com/phpredis/phpredis
     * @return RedisClient
     */
    public static function getClient(): RedisClient
    {
        if (is_null(static::$instance)) {
            static::$instance = static::getInstance();
        }

        return static::$redis;
    }
}
