<?php

namespace Bow\Support;

class Collection implements \Countable, \JsonSerializable, \IteratorAggregate, \ArrayAccess
{
    /**
     * The collection store
     *
     * @var array
     */
    protected $storage = [];

    /**
     * Collection constructor
     *
     * @param array $arr
     */
    public function __construct(array $arr = [])
    {
        $this->storage = $arr;
    }

    /**
     * The first element of the list
     *
     * @return mixed
     */
    public function first()
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
    public function get($key, $default = null)
    {
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
    public function values()
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
    public function keys()
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
    public function count()
    {
        return count($this->storage);
    }

    /**
     * To retrieve a value or value collection form d'instance de collection.
     *
     * @param string $key
     *
     * @return Collection
     */
    public function collectionify($key)
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
     *
     * @return Collection
     */
    public function delete($key)
    {
        unset($this->storage[$key]);

        return $this;
    }

    /**
     * Modify an entry in the collection or the addition if not
     *
     * @param string $key
     * @param mixed  $value
     *
     * @return mixed
     */
    public function set($key, $value)
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
    public function each(callable $cb)
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
     *
     * @throws \ErrorException
     */
    public function merge($array)
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
    public function map($cb)
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
    public function filter($cb)
    {
        $r = [];

        foreach ($this->storage as $key => $value) {
            if (call_user_func_array($cb, [$value, $key])) {
                $r[] = $this->storage[$key];
            }
        }

        return new Collection($r);
    }

    /**
     * Fill storage
     *
     * @param mixed $data
     * @param int   $offset
     *
     * @return array
     */
    public function fill($data, $offset)
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
     * @param mixed    $next
     *
     * @return self
     */
    public function reduce($cb, $next = null)
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
     * @param  $sep
     * @return string
     */
    public function implode($sep)
    {
        return implode($sep, $this->toArray());
    }

    /**
     * Sum
     *
     * @param callable $cb
     *
     * @return int
     */
    public function sum($cb = null)
    {
        $sum = 0;

        $this->recursive(
            $this->storage,
            function ($value) use (& $sum) {
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
     * @param callable $cb
     *
     * @return number
     */
    public function max($cb = null)
    {
        return $this->aggregate($cb, 'max');
    }

    /**
     * Max
     *
     * @param callable $cb
     * @return number
     */
    public function min($cb = null)
    {
        return $this->aggregate($cb, 'min');
    }

    /**
     * Aggregate Execute max|min
     *
     * @param callable $cb
     * @param string   $type
     *
     * @return number
     */
    private function aggregate($cb = null, $type)
    {
        $data = [];

        $this->recursive(
            $this->storage,
            function ($value) use (& $data) {
                if (is_numeric($value)) {
                    $data[] = $value;
                }
            }
        );

        $r = call_user_func_array($type, $data);

        if (is_callable($cb)) {
            call_user_func_array($cb, [$r]);
        }

        return $r;
    }

    /**
     * Returns the key list and return an instance of Collection.
     *
     * @param  array $except
     * @return Collection
     */
    public function excepts(array $except)
    {
        $data = [];

        $this->recursive(
            $this->storage,
            function ($value, $key) use (& $data, $except) {
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
    public function ignores(array $ignores)
    {
        $data = [];

        $this->recursive(
            $this->storage,
            function ($value, $key) use (& $data, $ignores) {
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
    public function reverse()
    {
        return new Collection(array_reverse($this->storage));
    }

    /**
     * Update an existing value in the collection
     *
     * @param  string|integer $key
     * @param  mixed          $data
     * @param  boolean        $overide
     * @return boolean
     */
    public function update($key, $data, $overide = false)
    {
        if (!$this->has($key)) {
            return false;
        }

        if (!is_array($this->storage[$key]) || $overide === true) {
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
     * Launches a generator
     *
     * @return \Generator
     */
    public function yieldify()
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
    public function toJson($option = 0)
    {
        return json_encode($this->storage, $option);
    }

    /**
     * Length of the collection
     *
     * @return int
     */
    public function length()
    {
        return count($this->storage);
    }

    /**
     * Deletes the first item in the collection
     *
     * @return mixed
     */
    public function shift()
    {
        $data = $this->storage;

        return array_shift($data);
    }

    /**
     * Deletes the last item in the collection
     *
     * @return mixed
     */
    public function pop()
    {
        return array_pop($this->storage);
    }

    /**
     * Returns the elements of the collection in table format
     *
     * @return array
     */
    public function toArray()
    {
        $collection = [];

        $this->recursive(
            $this->storage,
            function ($value, $key) use (& $collection) {
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
     * @return mixed
     */
    public function all()
    {
        return $this->storage;
    }

    /**
     * Add after the last item in the collection
     *
     * @param  mixed      $value
     * @param  int|string $key
     * @return mixed
     */
    public function push($value, $key = null)
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
     * @param array    $data
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
     *
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
     *
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
     */
    public function offsetSet($offset, $value)
    {
        $this->set($offset, $value);
    }

    /**
     * offsetUnset
     *
     * @param mixed $offset
     */
    public function offsetUnset($offset)
    {
        unset($this->storage[$offset]);
    }
}
