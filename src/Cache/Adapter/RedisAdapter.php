<?php

namespace Bow\Cache\Adapter;

use Redis;
use Bow\Cache\Adapter\CacheAdapterInterface;
use Bow\Database\Redis as RedisStore;

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
        $this->redis = RedisStore::getClient();

        if (isset($config["prefix"])) {
            $this->redis->setOption(Redis::OPT_PREFIX, $config["prefix"]);
        }

        $this->redis->select($config["database"] ?? 0);
    }

    /**
     * Ping the redis service
     *
     * @param ?string $message
     */
    public function ping(?string $message = null)
    {
        $this->redis->ping($message);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function add(string $key, mixed $data, ?int $time = null): bool
    {
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

        return $this->redis->set($key, $content, $options);
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
        if (!$this->has($key)) {
            return is_callable($default) ? $default() : $default;
        }

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
