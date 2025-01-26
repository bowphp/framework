<?php

declare(strict_types=1);

namespace Bow\Cache\Adapter;

use Memcached;

class MemcachedAdapter implements CacheAdapterInterface
{
    /**
     * The Memcached instance
     *
     * @var Memcached
     */
    private Memcached $memcached;

    /**
     * MemcachedAdapter constructor
     *
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->memcached = new Memcached();
        
        // Add servers from config
        foreach ($config['servers'] as $server) {
            $this->memcached->addServer(
                $server['host'] ?? '127.0.0.1',
                $server['port'] ?? 11211,
                $server['weight'] ?? 0
            );
        }
    }

    /**
     * Add new enter in the cache system
     *
     * @param string $key
     * @param mixed $data
     * @param ?int $time
     * @return bool
     */
    public function add(string $key, mixed $data, ?int $time = null): bool
    {
        return $this->memcached->add($key, $data, $time ?? 0);
    }

    /**
     * Set a new enter
     *
     * @param string $key
     * @param mixed $data
     * @param ?int $time
     * @return bool
     */
    public function set(string $key, mixed $data, ?int $time = null): bool
    {
        return $this->memcached->set($key, $data, $time ?? 0);
    }

    /**
     * Add many item
     *
     * @param array $data
     * @return bool
     */
    public function addMany(array $data): bool
    {
        return $this->memcached->setMulti($data);
    }

    /**
     * Adds a cache that will persist
     *
     * @param string $key The cache key
     * @param mixed $data
     * @return bool
     */
    public function forever(string $key, mixed $data): bool
    {
        return $this->memcached->set($key, $data, 0);
    }

    /**
     * Add new enter in the cache system
     *
     * @param string $key The cache key
     * @param array $data
     * @return bool
     */
    public function push(string $key, array $data): bool
    {
        $existing = $this->get($key, []);
        if (!is_array($existing)) {
            $existing = [];
        }
        
        $existing[] = $data;
        return $this->set($key, $existing);
    }

    /**
     * Retrieve an entry in the cache
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $value = $this->memcached->get($key);
        return $value !== false ? $value : $default;
    }

    /**
     * Increase the cache expiration time
     *
     * @param string $key
     * @param int $time
     * @return bool
     */
    public function addTime(string $key, int $time): bool
    {
        $value = $this->get($key);
        if ($value === null) {
            return false;
        }

        return $this->set($key, $value, $time);
    }

    /**
     * Retrieves the cache expiration time
     *
     * @param string $key
     * @return int|bool|string
     */
    public function timeOf(string $key): int|bool|string
    {
        return $this->memcached->getServerByKey($key);
    }

    /**
     * Delete an entry in the cache
     *
     * @param string $key
     * @return bool
     */
    public function forget(string $key): bool
    {
        return $this->memcached->delete($key);
    }

    /**
     * Check for an entry in the cache.
     *
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool
    {
        return $this->memcached->get($key) !== false;
    }

    /**
     * Check if the cache has expired
     *
     * @param string $key
     * @return bool
     */
    public function expired(string $key): bool
    {
        return !$this->has($key);
    }

    /**
     * Clear all cache
     *
     * @return void
     */
    public function clear(): void
    {
        $this->memcached->flush();
    }
} 
