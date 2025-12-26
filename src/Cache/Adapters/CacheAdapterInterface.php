<?php

namespace Bow\Cache\Adapters;

interface CacheAdapterInterface
{
    /**
     * Set a new enter
     *
     * @param  string $key
     * @param  mixed  $data
     * @param  ?int   $time
     * @return bool
     */
    public function set(string $key, mixed $data, ?int $time = null): bool;

    /**
     * Add many item
     *
     * @param  array $data
     * @return bool
     */
    public function setMany(array $data): bool;

    /**
     * Adds a cache that will persist
     *
     * @param  string $key  The cache key
     * @param  mixed  $data
     * @return bool
     */
    public function forever(string $key, mixed $data): bool;

    /**
     * Add new enter in the cache system
     *
     * @param  string $key  The cache key
     * @param  mixed  $data
     * @return bool
     */
    public function push(string $key, array $data): bool;

    /**
     * Retrieve an entry in the cache
     *
     * @param  string $key
     * @param  mixed  $default
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * Increase the cache expiration time
     *
     * @param  string $key
     * @param  int    $time
     * @return bool
     */
    public function setTime(string $key, int $time): bool;

    /**
     * Retrieves the cache expiration time
     *
     * @param  string $key
     * @return int|bool|string
     */
    public function timeOf(string $key): int|bool|string;

    /**
     * Remove an entry from the cache
     *
     * @param  string $key
     * @return bool
     */
    public function forget(string $key): bool;

    /**
     * Retrieve an entry from the cache or store it if it does not exist
     *
     * @param  string   $key
     * @param  int      $time
     * @param  callable $callback
     * @return mixed
     */
    public function remember(string $key, int $time, callable $callback): mixed;

    /**
     * Increment the value of an entry in the cache
     *
     * @param  string $key
     * @param  int    $value
     * @return int
     */
    public function increment(string $key, int $value = 1): int;

    /**
     * Decrement the value of an entry in the cache
     *
     * @param  string $key
     * @param  int    $value
     * @return int
     */
    public function decrement(string $key, int $value = 1): int;

    /**
     * Check for an entry in the cache.
     *
     * @param  string $key
     * @return bool
     */
    public function has(string $key): bool;

    /**
     * Check if the cache has expired
     *
     * @param  string $key
     * @return bool
     */
    public function expired(string $key): bool;

    /**
     * Clear all cache
     *
     * @return void
     */
    public function clear(): void;
}
