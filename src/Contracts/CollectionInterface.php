<?php

declare(strict_types=1);

namespace Bow\Contracts;

interface CollectionInterface
{
    /**
     * Check for existence of a key in the session collection
     *
     * @param  string $key
     * @return boolean
     */
    public function has($key);

    /**
     * Check if a collection is empty.
     *
     * @return boolean
     */
    public function isEmpty();

    /**
     * Allows to recover a value or value collection.
     *
     * @param  string $key
     * @param  mixed  $default
     * @return mixed
     */
    public function get($key, $default = null);

    /**
     * Add an entry to the collection
     *
     * @param  string $key
     * @param  $data
     * @param  bool   $next
     * @return self
     */
    public function add($key, $data, $next = false);


    /**
     * Delete an entry in the collection
     *
     * @param  string $key
     * @return self
     */
    public function remove($key);

    /**
     * Modify an entry in the collection
     *
     * @param  string $key
     * @param  mixed  $value
     * @return self
     */
    public function set($key, $value);

    /**
     * Return all the entries of the collection as an array
     *
     * @return array
     */
    public function toArray();

    /**
     * Return all entries of the collection as an object
     *
     * @return array
     */
    public function toObject();
}
