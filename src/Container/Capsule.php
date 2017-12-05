<?php

namespace Bow\Container;

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
     * @var array
     */
    private $parameters = [];

    /**
     * @var Capsule
     */
    private static $instance;

    /**
     * @return Capsule
     */
    public static function getInstance()
    {
        if (is_null(static::$instance)) {
            static::$instance = new static();
        }

        return static::$instance;
    }

    /**
     * @param string $key
     * @return mixed
     */
    public function make($key)
    {
        if (isset($this->factories[$key])) {
            return call_user_func_array(
                $this->factories[$key],
                array_merge([$this], $this->parameters)
            );
        }

        if (isset($this->instances[$key])) {
            return $this->instances[$key];
        }

        if (!isset($this->registers[$key])) {
            return $this->resolve($key);
        }

        if (is_callable($this->registers[$key])) {
            return $this->instances[$key] = call_user_func_array(
                $this->registers[$key],
                array_merge([$this], $this->parameters)
            );
        }

        if (!is_object($this->registers[$key])) {
            return $this->instances[$key] = $this->resolve($key);
        }

        if (method_exists($this->registers[$key], '__invoke')) {
            return  $this->instances[$key] = $this->registers[$key]($this);
        }

        return null;
    }

    /**
     * @param $key
     * @param array $parameters
     */
    public function makeWith($key, $parameters = [])
    {
        $this->parameters = $parameters;
        $this->resolve($key);
    }

    /**
     * @param string $key
     * @param mixed  $value
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
     * @param string   $key
     * @param $instance
     */
    public function instance($key, $instance)
    {
        $this->instances[$key] = $instance;
    }

    /**
     * @param string $key
     * @return mixed
     * @throws \ErrorException
     */
    private function resolve($key)
    {
        $reflection = new \ReflectionClass($key);

        if (!$reflection->isInstantiable()) {
            return $key;
        }

        $constructor = $reflection->getConstructor();
        if (!$constructor) {
            return $reflection->newInstance();
        }

        $parameters = $constructor->getParameters();
        $parameters_lists = [];

        foreach ($parameters as $parameter) {
            if ($parameter->getClass()) {
                $parameters_lists[] = $this->make($parameter->getClass()->getName());
            } else {
                $parameters_lists[] = $parameter->getDefaultValue();
            }
        }

        if (!empty($this->parameters)) {
            $parameters_lists = $this->parameters;
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
