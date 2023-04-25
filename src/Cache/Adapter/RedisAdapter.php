<?php

namespace Bow\Cache\Adapter;

use Bow\Cache\Adapter\CacheAdapterInterface;

class RedisAdapter implements CacheAdapterInterface
{
    /**
     * Define the cache config
     *
     * @var array
     */
    private array $config;

    /**
     * The meta data
     *
     * @var bool
     */
    private bool $with_meta = false;

    /**
     * RedisAdapter constructor.
     *
     * @param array $config
     * @return mixed
     */
    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * @inheritDoc
     */
    public function add(string $key, mixed $data, ?int $time = null): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function addMany(array $data): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function forever(string $key, mixed $data): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function push(string $key, array $data): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function get(string $key, mixed $default = null): mixed
    {
    }

    /**
     * @inheritDoc
     */
    public function addTime(string $key, int $time): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function timeOf(string $key): int|bool|string
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function forget(string $key): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function has(string $key): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function expired(string $key): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function clear(): void
    {
    }

    /**
     * @inheritDoc
     */
    private function makeHash(string $key): string
    {
        return hash('sha256', $key);
    }
}
