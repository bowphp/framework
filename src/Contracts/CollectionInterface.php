<?php

declare(strict_types=1);

namespace Bow\Contracts;

interface CollectionInterface
{
    /**
     * Check for existence of a key in the session collection
     *
     * @param  string $key
     * @return bool
     */
    public function has($key): bool;

    /**
     * Check if a collection is empty.
     *
     * @return bool
     */
    public function isEmpty(): bool;

    /**
     * Allows to recover a value or value collection.
     *
     * @param  string $key
     * @param  mixed  $default
     * @return mixed
     */
    public function get(mixed $key, mixed $default = null): mixed;

    /**
     * Add an entry to the collection
     *
     * @param  string $key
     * @param  mixed $data
     * @param  bool  $next
     * @return CollectionInterface
     */
    public function add(string $key, mixed $data, bool $next = false);


    /**
     * Delete an entry in the collection
     *
     * @param  string $key
     * @return CollectionInterface
     */
    public function remove(string $key): CollectionInterface;

    /**
     * Modify an entry in the collection
     *
     * @param  string $key
     * @param  mixed  $value
     * @return CollectionInterface
     */
    public function set(string $key, mixed $value): CollectionInterface;

    /**
     * Return all the entries of the collection as an array
     *
     * @return array
     */
    public function toArray(): array;

    /**
     * Return all entries of the collection as an object
     *
     * @return array
     */
    public function toObject(): array;
}
