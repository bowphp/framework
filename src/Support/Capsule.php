<?php

namespace Bow\Support;

class Capsule implements \ArrayAccess
{
    /**
     * @var array
     */
    private $container = [];

    /**
     * @param string $key
     * @return mixed
     */
    public function make($key)
    {
        if (is_callable($this->container[$key])) {
            return $this->container[$key]($this);
        }

        if (! is_object($this->container[$key])) {
            return $this[$key];
        }

        if (method_exists($this->container[$key], '__invoke')) {
            return $this->container[$key]($this);
        }

        return null;
    }

    /**
     * @param string $key
     * @return mixed
     */
    public function get($key)
    {
        return $this[$key];
    }

    /**
     * @param string $key
     * @param mixed $value
     */
    public function set($key, $value)
    {
        $this[$key] = $value;
    }

    /**
     * @inheritDoc
     */
    public function offsetExists($offset)
    {
        return isset($this->container[$offset]);
    }

    /**
     * @inheritDoc
     */
    public function offsetGet($offset)
    {
        return $this->make($offset);
    }

    /**
     * @inheritDoc
     */
    public function offsetSet($offset, $value)
    {
        $this->container[$offset] = $value;
    }

    /**
     * @inheritDoc
     */
    public function offsetUnset($offset)
    {
        unset($this->container[$offset]);
    }
}