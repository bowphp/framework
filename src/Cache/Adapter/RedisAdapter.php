<?php

namespace Bow\Cache\Adapter;

use Redis;
use Bow\Cache\Adapter\CacheAdapterInterface;

class RedisAdapter implements CacheAdapterInterface
{
    /**
     * Define the php-redis instance
     *
     * @var Redis
     */
    private Redis $redis;

    /**
     * RedisAdapter constructor.
     *
     * @param array $config
     * @return mixed
     */
    public function __construct(array $config)
    {
        $this->redis = new Redis();
        $this->redis->connect($config["host"], $config["port"]);
    }

    /**
     * @inheritDoc
     */
    public function add(string $key, mixed $data, ?int $time = null): bool
    {
        $options = [];

        if (!is_null($time)) {
            $options = [
                'EX' => $time
            ];
        }

        return $this->redis->set($key, $data, $options);
    }

    /**
     * @inheritDoc
     */
    public function addMany(array $data): bool
    {
        $return = true;

        foreach ($data as $attribute => $value) {
            $return = $this->add($attribute, $value);
        }

        return $return;
    }

    /**
     * @inheritDoc
     */
    public function forever(string $key, mixed $data): bool
    {
        $this->add($key, $data);

        return $this->redis->persist($key);
    }

    /**
     * @inheritDoc
     */
    public function push(string $key, array $data): bool
    {
        return $this->redis->append($key, $data);
    }

    /**
     * @inheritDoc
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $value = $this->redis->get($key);

        return is_null($value) ? $default : $value;
    }

    /**
     * @inheritDoc
     */
    public function addTime(string $key, int $time): bool
    {
        return $this->redis->expire($key, $time);
    }

    /**
     * @inheritDoc
     */
    public function timeOf(string $key): int|bool|string
    {
        return $this->redis->ttl($key);
    }

    /**
     * @inheritDoc
     */
    public function forget(string $key): bool
    {
        return $this->redis->del($key);
    }

    /**
     * @inheritDoc
     */
    public function has(string $key): bool
    {
        return $this->redis->exists($key);
    }

    /**
     * @inheritDoc
     */
    public function expired(string $key): bool
    {
        return $this->redis->expire($key);
    }

    /**
     * @inheritDoc
     */
    public function clear(): void
    {
        $this->redis->flushdb();
    }
}
