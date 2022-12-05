<?php

declare(strict_types=1);

namespace Bow\Support;

use Generator as PHPGenerator;

class Collection implements \Countable, \JsonSerializable, \IteratorAggregate, \ArrayAccess
{
    /**
     * The collection store
     *
     * @var array
     */
    protected array $storage = [];

    /**
     * Collection constructor
     *
     * @param array $arr
     */
    public function __construct(array $storage = [])
    {
        $this->storage = $storage;
    }

    /**
     * The first element of the list
     *
     * @return mixed
     */
    public function first(): mixed
    {
        return current($this->storage);
    }

    /**
     * The last element of the list
     *
     * @return array
     */
    public function last()
    {
        $element = end($this->storage);

        reset($this->storage);

        return $element;
    }

    /**
     * Check existence of a key in the session collection
     *
     * @param  string $key
     * @param  bool   $strict
     * @return boolean
     */
    public function has($key, $strict = false)
    {
        // When $strict is true, he check $key not how a key but a value.
        $isset = isset($this->storage[$key]);

        if ($isset) {
            if ($strict === true) {
                $isset = $isset && !empty($this->storage[$key]);
            }
        }

        return $isset;
    }

    /**
     * Check if a collection is empty.
     *
     * @return boolean
     */
    public function isEmpty()
    {
        $isEmpty = empty($this->storage);

        if ($isEmpty === false) {
            if ($this->length() == 1) {
                if (is_null($this->values()[0])) {
                    $isEmpty = true;
                }
            }
        }

        return $isEmpty;
    }

    /**
     * Allows to recover a value or value collection.
     *
     * @param string $key
     * @param mixed  $default
     *
     * @return mixed
     */
    public function get($key = null, $default = null)
    {
        if (is_null($key)) {
            return $this->storage;
        }

        if ($this->has($key)) {
            return $this->storage[$key] == null
                ? $default
                : $this->storage[$key];
        }

        if ($default !== null) {
            if (is_callable($default)) {
                return call_user_func($default);
            }

            return $default;
        }

        return null;
    }

    /**
     * Get the list of values of collection
     *
     * @return Collection
     */
    public function values(): Collection
    {
        $r = [];

        foreach ($this->storage as $value) {
            array_push($r, $value);
        }

        return new Collection($r);
    }

    /**
     * Get the list of keys of collection
     *
     * @return Collection
     */
    public function keys(): Collection
    {
        $r = [];

        foreach ($this->storage as $key => $value) {
            array_push($r, $key);
        }

        return new Collection($r);
    }

    /**
     * Count the collection element.
     *
     * @return int
     */
    public function count(): int
    {
        return count($this->storage);
    }

    /**
     * To retrieve a value or value collection form d'instance de collection.
     *
     * @param string $key
     * @return Collection
     */
    public function collectionify(string $key): Collection
    {
        $data = [];

        if ($this->has($key)) {
            $data = $this->storage[$key];

            if (!is_array($data)) {
                $data = [$data];
            }
        }

        return new Collection($data);
    }

    /**
     * Delete an entry in the collection
     *
     * @param string $key
     * @return Collection
     */
    public function delete(string $key): Collection
    {
        unset($this->storage[$key]);

        return $this;
    }

    /**
     * Modify an entry in the collection or the addition if not
     *
     * @param string $key
     * @param mixed  $value
     * @return mixed
     */
    public function set(mixed $key, mixed $value): mixed
    {
        if ($this->has($key)) {
            $old = $this->storage[$key];

            $this->storage[$key] = $value;

            return $old;
        }

        $this->storage[$key] = $value;

        return null;
    }

    /**
     * Browse all the values of the collection
     *
     * @param callable $cb
     */
    public function each(callable $cb): void
    {
        foreach ($this->storage as $key => $value) {
            call_user_func_array($cb, [$value, $key]);
        }
    }

    /**
     * Merge the collection with a painting or other collection
     *
     * @param Collection|array $array
     * @return Collection
     * @throws \ErrorException
     */
    public function merge($array): Collection
    {
        if (is_array($array)) {
            $this->storage = array_merge(
                $this->storage,
                $array
            );
        } elseif ($array instanceof Collection) {
            $this->storage = array_merge(
                $this->storage,
                $array->toArray()
            );
        } else {
            throw new \ErrorException(
                'Must be take 1 parameter to be array or Collection',
                E_ERROR
            );
        }

        return $this;
    }
    /**
     * Map
     *
     * @param callable $cb
     *
     * @return Collection
     */
    public function map(callable $cb): Collection
    {
        $data = $this->storage;

        $new = [];

        foreach ($data as $key => $value) {
            $new[$key] = call_user_func_array($cb, [$value, $key]);
        }

        return new Collection($new);
    }

    /**
     * Filter
     *
     * @param callable $cb
     *
     * @return Collection
     */
    public function filter(callable $cb): Collection
    {
        $data = [];

        foreach ($this->storage as $key => $value) {
            if (call_user_func_array($cb, [$value, $key])) {
                $data[] = $this->storage[$key];
            }
        }

        return new Collection($data);
    }

    /**
     * Fill storage
     *
     * @param mixed $data
     * @param int   $offset
     * @return array
     */
    public function fill(mixed $data, int $offset): mixed
    {
        $old = $this->storage;

        $len = count($old);

        for ($i = $len, $len += $offset; $i < $len; $i++) {
            $this->storage[$i] = $data;
        }

        return $old;
    }

    /**
     * Reduce
     *
     * @param callable $cb
     * @param mixed  $next
     * @return Collection
     */
    public function reduce(callable $cb, $next = null): Collection
    {
        foreach ($this->storage as $key => $current) {
            $next = call_user_func_array($cb, [
                $next, $current, $key, $this->storage
            ]);
        }

        return $this;
    }

    /**
     * Implode
     *
     * @param string $sep
     * @return string
     */
    public function implode(string $sep): string
    {
        return implode($sep, $this->toArray());
    }

    /**
     * Sum
     *
     * @param callable $cb
     * @return int|float
     */
    public function sum(callable $cb = null): int|float
    {
        $sum = 0;

        $this->recursive(
            $this->storage,
            function ($value) use (&$sum) {
                if (is_numeric($value)) {
                    $sum += $value;
                }
            }
        );

        if ($cb !== null) {
            call_user_func_array($cb, [$sum]);
        }

        return $sum;
    }

    /**
     * Max
     *
     * @param ?callable $cb
     * @return int|float
     */
    public function max(?callable $cb = null): int|float
    {
        return $this->aggregate('max', $cb);
    }

    /**
     * Max
     *
     * @param ?callable $cb
     * @return int|float
     */
    public function min(?callable $cb = null)
    {
        return $this->aggregate('min', $cb);
    }

    /**
     * Aggregate Execute max|min
     *
     * @param callable $cb
     * @param string   $type
     * @return int|float
     */
    private function aggregate($type, $cb = null)
    {
        $data = [];

        $this->recursive(
            $this->storage,
            function ($value) use (&$data) {
                if (is_numeric($value)) {
                    $data[] = $value;
                }
            }
        );

        $result = call_user_func_array($type, $data);

        if (is_callable($cb)) {
            call_user_func_array($cb, [$result]);
        }

        return $result;
    }

    /**
     * Returns the key list and return an instance of Collection.
     *
     * @param  array $except
     * @return Collection
     */
    public function excepts(array $except): Collection
    {
        $data = [];

        $this->recursive(
            $this->storage,
            function ($value, $key) use (&$data, $except) {
                if (in_array($key, $except)) {
                    $data[$key] = $value;
                }
            }
        );

        return new Collection($data);
    }

    /**
     * Ignore the key that is given to it and return an instance of Collection.
     *
     * @param  array $ignores
     * @return Collection
     */
    public function ignores(array $ignores): Collection
    {
        $data = [];

        $this->recursive(
            $this->storage,
            function ($value, $key) use (&$data, $ignores) {
                if (!in_array($key, $ignores)) {
                    $data[$key] = $value;
                }
            }
        );

        return new Collection($data);
    }

    /**
     * Reverse collection
     *
     * @return Collection
     */
    public function reverse(): Collection
    {
        return new Collection(array_reverse($this->storage));
    }

    /**
     * Update an existing value in the collection
     *
     * @param  string|integer $key
     * @param  mixed $data
     * @param  bool $override
     * @return bool
     */
    public function update(mixed $key, mixed $data, bool $override = false): bool
    {
        if (!$this->has($key)) {
            return false;
        }

        if (!is_array($this->storage[$key]) || $override === true) {
            $this->storage[$key] = $data;

            return true;
        }

        if (!is_array($data)) {
            $data = [$data];
        }

        $this->storage[$key] = array_merge($this->storage[$key], $data);

        return true;
    }

    /**
     * Launches the collection content as generator
     *
     * @return PHPGenerator
     */
    public function yieldify(): PHPGenerator
    {
        foreach ($this->storage as $key => $value) {
            yield (object) [
                'value' => $value,
                'key' => $key,
                'done' => false
            ];
        }

        yield (object) [
            'value' => null,
            'key' => null,
            'done' => true
        ];
    }

    /**
     * Get the data in JSON format
     *
     * @param  int $option
     * @return string
     */
    public function toJson(int $option = 0): string
    {
        return json_encode($this->storage, $option);
    }

    /**
     * Length of the collection
     *
     * @return int
     */
    public function length(): int
    {
        return count($this->storage);
    }

    /**
     * Deletes the first item in the collection
     *
     * @return mixed
     */
    public function shift(): mixed
    {
        $data = $this->storage;

        return array_shift($data);
    }

    /**
     * Deletes the last item in the collection
     *
     * @return array
     */
    public function pop(): mixed
    {
        return array_pop($this->storage);
    }

    /**
     * Returns the elements of the collection in table format
     *
     * @return array
     */
    public function toArray(): array
    {
        $collection = [];

        $this->recursive(
            $this->storage,
            function ($value, $key) use (&$collection) {
                if (is_object($value)) {
                    $collection[$key] = (array) $value;
                } else {
                    $collection[$key] = $value;
                }
            }
        );

        return $collection;
    }

    /**
     * Returns the elements of the collection
     *
     * @return array
     */
    public function all(): array
    {
        return $this->storage;
    }

    /**
     * Add after the last item in the collection
     *
     * @param  mixed $value
     * @param  int|string $key
     * @return Collection
     */
    public function push(mixed $value, mixed $key = null): Collection
    {
        if ($key == null) {
            $this->storage[] = $value;
        } else {
            $this->storage[$key] = $value;
        }

        return $this;
    }

    /**
     * Recursive walk of a table or object
     *
     * @param array  $data
     * @param callable $cb
     */
    private function recursive(array $data, callable $cb)
    {
        foreach ($data as $key => $value) {
            if (is_array($value) || is_object($value)) {
                $this->recursive((array) $value, $cb);
            } else {
                $cb($value, $key);
            }
        }
    }

    /**
     * __get
     *
     * @param mixed $name
     * @return mixed
     */
    public function __get($name)
    {
        return $this->get($name);
    }

    /**
     * __set
     *
     * @param mixed $name
     * @param mixed $value
     * @return void
     */
    public function __set($name, $value)
    {
        $this->storage[$name] = $value;
    }

    /**
     * __isset
     *
     * @param mixed $name
     * @return bool
     */
    public function __isset($name)
    {
        return $this->has($name);
    }

    /**
     * __unset
     *
     * @param mixed $name
     * @return void
     */
    public function __unset($name)
    {
        $this->delete($name);
    }

    /**
     * __toString
     *
     * @return string
     */
    public function __toString()
    {
        return $this->toJson();
    }

    /**
     * jsonSerialize
     *
     * @return string
     */
    public function jsonSerialize()
    {
        return $this->storage;
    }

    /**
     * getIterator
     *
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->storage);
    }

    /**
     * offsetExists
     *
     * @param mixed $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        return $this->has($offset);
    }

    /**
     * offsetGet
     *
     * @param mixed $offset
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    /**
     * offsetSet
     *
     * @param mixed $offset
     * @param mixed $value
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        $this->set($offset, $value);
    }

    /**
     * offsetUnset
     *
     * @param mixed $offset
     * @return void
     */
    public function offsetUnset($offset)
    {
        unset($this->storage[$offset]);
    }
}
