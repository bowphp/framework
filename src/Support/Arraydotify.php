<?php

declare(strict_types=1);

namespace Bow\Support;

use ArrayAccess;

class Arraydotify implements ArrayAccess
{
    /**
     * The array collection in dot notation
     *
     * @var array
     */
    private array $items = [];

    /**
     * The original array structure
     *
     * @var array
     */
    private array $origin = [];

    /**
     * Arraydotify constructor.
     *
     * @param array $items
     */
    public function __construct(array $items = [])
    {
        $this->origin = $items;
        $this->items = $this->dotify($items);
    }

    /**
     * Convert a multi-dimensional array to dot notation
     *
     * @param  array  $items
     * @param  string $prepend
     * @return array
     */
    private function dotify(array $items, string $prepend = ''): array
    {
        $dot = [];

        foreach ($items as $key => $value) {
            $dotKey = $prepend . $key;

            if (is_array($value) || is_object($value)) {
                $dot = array_merge(
                    $dot,
                    $this->dotify((array) $value, $dotKey . '.')
                );
            } else {
                $dot[$dotKey] = $value;
            }
        }

        return $dot;
    }

    /**
     * Make array dotify (static factory method)
     *
     * @param  array $items
     * @return Arraydotify
     */
    public static function make(array $items = []): Arraydotify
    {
        return new self($items);
    }

    /**
     * Get a value from the array using dot notation
     *
     * @param mixed $offset
     * @return mixed
     */
    public function offsetGet(mixed $offset): mixed
    {
        // Try to get from dotified items first
        if (isset($this->items[$offset])) {
            return $this->items[$offset];
        }

        // Try to find nested array in origin
        return $this->find($this->origin, $offset);
    }

    /**
     * Check if a key exists in the array using dot notation
     *
     * @param mixed $offset
     * @return bool
     */
    public function offsetExists(mixed $offset): bool
    {
        if (isset($this->items[$offset])) {
            return true;
        }

        $value = $this->find($this->origin, $offset);

        return $value !== null && (!is_array($value) || !empty($value));
    }

    /**
     * Find a value in the original array using dot notation
     *
     * @param  array  $array
     * @param  string $key
     * @return mixed
     */
    private function find(array $array, string $key): mixed
    {
        if (empty($key)) {
            return null;
        }

        $keys = explode('.', $key);

        foreach ($keys as $segment) {
            if (!is_array($array) || !array_key_exists($segment, $array)) {
                return null;
            }

            $array = $array[$segment];
        }

        return $array;
    }

    /**
     * Set a value in the array using dot notation
     *
     * @param mixed $offset
     * @param mixed $value
     * @return void
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        if (is_null($offset)) {
            $this->origin[] = $value;
        } else {
            $this->dataSet($this->origin, $offset, $value);
        }

        // Rebuild dotified array
        $this->items = $this->dotify($this->origin);
    }

    /**
     * Set a value in an array using dot notation
     *
     * @param  array  $array
     * @param  string $key
     * @param  mixed  $value
     * @return void
     */
    private function dataSet(array &$array, string $key, mixed $value): void
    {
        $keys = explode('.', $key);

        while (count($keys) > 1) {
            $segment = array_shift($keys);

            // Create nested array if it doesn't exist or isn't an array
            if (!isset($array[$segment]) || !is_array($array[$segment])) {
                $array[$segment] = [];
            }

            $array = &$array[$segment];
        }

        $array[array_shift($keys)] = $value;
    }

    /**
     * Unset a value from the array using dot notation
     *
     * @param mixed $offset
     * @return void
     */
    public function offsetUnset(mixed $offset): void
    {
        if (isset($this->items[$offset])) {
            unset($this->items[$offset]);
        }

        $this->dataUnset($this->origin, $offset);

        // Rebuild dotified array
        $this->items = $this->dotify($this->origin);
    }

    /**
     * Unset a value from an array using dot notation
     *
     * @param  array  $array
     * @param  string $key
     * @return void
     */
    private function dataUnset(array &$array, string $key): void
    {
        $keys = explode('.', $key);

        while (count($keys) > 1) {
            $segment = array_shift($keys);

            if (!isset($array[$segment]) || !is_array($array[$segment])) {
                return;
            }

            $array = &$array[$segment];
        }

        unset($array[array_shift($keys)]);
    }

    /**
     * Get the original array
     *
     * @return array
     */
    public function toArray(): array
    {
        return $this->origin;
    }

    /**
     * Get the dotified array
     *
     * @return array
     */
    public function getDotified(): array
    {
        return $this->items;
    }

    /**
     * Check if the array has a key using dot notation
     *
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool
    {
        return $this->offsetExists($key);
    }

    /**
     * Get a value using dot notation with a default fallback
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $value = $this->offsetGet($key);

        return $value ?? $default;
    }

    /**
     * Set a value using dot notation
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function set(string $key, mixed $value): void
    {
        $this->offsetSet($key, $value);
    }
}
