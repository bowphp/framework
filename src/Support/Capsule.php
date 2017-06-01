<?php

namespace Bow\Support;

class Capsule implements \ArrayAccess
{
    /**
     * @var array
     */
    private $registers = [];

    /**
     * @var array
     */
    private $instances = [];

    /**
     * @var array
     */
    private $factories = [];

    /**
     * @var array
     */
    private $key = [];

    /**
     * @param string $key
     * @return mixed
     */
    public function make($key)
    {
        if (isset($this->factories[$key])) {
            return $this->factories[$key]();
        }

        if (isset($this->instances[$key])) {
            return $this->instances[$key];
        }

        if (! isset($this->registers[$key])) {
            return null;
        }

        if (is_callable($this->registers[$key])) {
            return $this->instances[$key] = $this->registers[$key]($this);
        }

        if (! is_object($this->registers[$key])) {
            return $this->instances[$key] = $this->resolve($key);
        }

        if (method_exists($this->registers[$key], '__invoke')) {
            return  $this->instances[$key] = $this->registers[$key]($this);
        }

        return null;
    }

    /**
     * @param string $key
     * @param mixed $value
     */
    public function bind($key, $value)
    {
        $this->key[$key] = true;
        $this[$key] = $value;
    }

    /**
     * @param $key
     * @param \Closure $value
     */
    public function factory($key, \Closure $value)
    {
        $this->factories[$key] = $value;
    }

    /**
     * @param $instance
     */
    public function instance($instance)
    {
        $this->factories[] = $instance;
    }

    /**
     * @param string $key
     * @return mixed
     */
    private function resolve($key)
    {
        if (! $this->offsetExists($key)) {
            return null;
        }

        $reflection = new \ReflectionClass($key);

        if (! $reflection->isInstantiable()) {
            return $key;
        }

        $constructor = $reflection->getConstructor();
        if (! $constructor) {
            return $reflection->newInstance();
        }

        $parameters = $constructor->getParameters();
        $parameters_lists = [];

        foreach ($parameters as $parameter) {
            if ($parameter->getClass()) {
                $parameters_lists[] = $this->make($parameter->getName());
            } else {
                $parameters_lists[] = $parameter->getDefaultValue();
            }
        }

        return $reflection->newInstanceArgs($parameters_lists);
    }

    /**
     * @inheritDoc
     */
    public function offsetExists($offset)
    {
        return isset($this->key[$offset]);
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
        $this->registers[$offset] = $value;
    }

    /**
     * @inheritDoc
     */
    public function offsetUnset($offset)
    {
        unset($this->registers[$offset]);
    }
}