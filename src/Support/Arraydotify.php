<?php

declare(strict_types=1);

namespace Bow\Support;

use ArrayAccess;

class Arraydotify implements ArrayAccess
{
    /**
     * The array collection
     *
     * @var array
     */
    private array $items = [];

    /**
     * The origin array
     *
     * @var array
     */
    private array $origin = [];

    /**
     * Arraydotify constructor.
     *
     * @param array $items
     * @return void
     */
    public function __construct(array $items = [])
    {
        $this->items = $this->dotify($items);

        $this->origin = $items;
    }

    /**
     * Dotify action
     *
     * @param array $items
     * @param string $prepend
     * @return array
     */
    private function dotify(array $items, string $prepend = ''): array
    {
        $dot = [];

        foreach ($items as $key => $value) {
            if (!(is_array($value) || is_object($value))) {
                $dot[$prepend . $key] = $value;
                continue;
            }

            $value = (array)$value;

            $dot = array_merge($dot, $this->dotify(
                $value,
                $prepend . $key . '.'
            ));
        }

        return $dot;
    }

    /**
     * Make array dotify
     *
     * @param array $items
     * @return Arraydotify
     */
    public static function make(array $items = []): Arraydotify
    {
        return new Arraydotify($items);
    }

    /**
     * @inheritDoc
     */
    public function offsetGet($offset): mixed
    {
        if (!$this->offsetExists($offset)) {
            return null;
        }

        return $this->items[$offset] ?? $this->find($this->origin, $offset);
    }

    /**
     * @inheritDoc
     */
    public function offsetExists($offset): bool
    {
        if (isset($this->items[$offset])) {
            return true;
        }

        $array = $this->find($this->origin, $offset);

        return (is_array($array) && !empty($array));
    }

    /**
     * Find information to the origin array
     *
     * @param array $origin
     * @param string $segment
     * @return ?array
     */
    private function find(array $origin, string $segment): ?array
    {
        $parts = explode('.', $segment);

        $array = [];

        foreach ($parts as $key => $part) {
            if ($key != 0) {
                if (is_array($array) && is_null($array[$part] ?? null)) {
                    return null;
                }

                if (isset($array[$part]) && is_array($array[$part])) {
                    $array = &$array[$part];
                }

                continue;
            }

            if (!isset($origin[$part])) {
                return null;
            }

            if (!is_array($origin[$part])) {
                return [$origin[$part]];
            }

            $array = &$origin[$part];
        }

        return $array;
    }

    /**
     * @inheritDoc
     */
    public function offsetSet($offset, $value): void
    {
        $this->items[$offset] = $value;

        $this->items = $this->dotify($this->items);

        $this->updateOrigin();
    }

    /**
     * Update the original data
     *
     * @return void
     */
    private function updateOrigin(): void
    {
        foreach ($this->items as $key => $value) {
            $this->dataSet($this->origin, $key, $value);
        }
    }

    /**
     * Transform the dot access to array access
     *
     * @param mixed $array
     * @param string $key
     * @param mixed $value
     * @return void
     */
    private function dataSet(mixed &$array, string $key, mixed $value): void
    {
        $keys = explode('.', $key);

        while (count($keys) > 1) {
            $key = array_shift($keys);

            if (!isset($array[$key]) || !is_array($array[$key])) {
                $array[$key] = [];
            }

            $array = &$array[$key];
        }

        $array[array_shift($keys)] = $value;
    }

    /**
     * @inheritDoc
     */
    public function offsetUnset($offset): void
    {
        unset($this->items[$offset]);

        $this->items = $this->dotify($this->items);

        $this->updateOrigin();
    }
}
