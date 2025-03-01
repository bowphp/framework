<?php

declare(strict_types=1);

namespace Bow\Container;

use ArrayAccess;
use Closure;
use InvalidArgumentException;
use ReflectionClass;

class Capsule implements ArrayAccess
{
    /**
     * Represents the instance of Capsule
     *
     * @var ?Capsule
     */
    private static ?Capsule $instance = null;
    /**
     * The container register for bind by alias
     *
     * @var array
     */
    private array $registers = [];
    /**
     * The container register for instance
     *
     * @var array
     */
    private array $instances = [];
    /**
     * The container factory maker
     *
     * @var array
     */
    private array $factories = [];
    /**
     * Represents a cache collector
     *
     * @var array
     */
    private array $key = [];
    /**
     * Represents the compilation parameters
     *
     * @var array
     */
    private array $parameters = [];

    /**
     * Get instance of Capsule
     *
     * @return Capsule
     */
    public static function getInstance(): Capsule
    {
        if (is_null(static::$instance)) {
            static::$instance = new Capsule();
        }

        return static::$instance;
    }

    /**
     * Compilation with parameter
     *
     * @param string $key
     * @param array $parameters
     * @return mixed
     * @throws
     */
    public function makeWith(string $key, array $parameters = []): mixed
    {
        $this->parameters = $parameters;

        $resolved = $this->resolve($key);

        $this->parameters = [];

        return $resolved;
    }

    /**
     * Instantiate a class by its key
     *
     * @param string $key
     * @return mixed
     * @throws
     */
    private function resolve(string $key): mixed
    {
        $reflection = new ReflectionClass($key);

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
            if ($parameter->isDefaultValueAvailable()) {
                $parameters_lists[] = $parameter->getDefaultValue();
                continue;
            }
            if (!$parameter->isOptional()) {
                $parameters_lists[] = $this->make($parameter->getType()->getName());
            }
        }

        if (!empty($this->parameters)) {
            $parameters_lists = $this->parameters;

            $this->parameters = [];
        }

        return $reflection->newInstanceArgs($parameters_lists);
    }

    /**
     * Make the
     *
     * @param string $key
     * @return mixed
     * @throws
     */
    public function make(string $key): mixed
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
            return $this->instances[$key] = $this->registers[$key]();
        }

        return null;
    }

    /**
     * Add to register
     *
     * @param string $key
     * @param callable $value
     * @return Capsule
     */
    public function bind(string $key, callable $value): Capsule
    {
        $this->key[$key] = true;

        $this[$key] = $value;

        return $this;
    }

    /**
     * Register the instance of a class
     *
     * @param string $key
     * @param Closure|callable $value
     * @return Capsule
     */
    public function factory(string $key, Closure|callable $value): Capsule
    {
        $this->factories[$key] = $value;

        return $this;
    }

    /**
     * Saves the instance of a class
     *
     * @param string $key
     * @param mixed $instance
     * @return Capsule
     */
    public function instance(string $key, mixed $instance): Capsule
    {
        if (!is_object($instance)) {
            throw new InvalidArgumentException(
                "The parameter $instance must be an object."
            );
        }

        $this->instances[$key] = $instance;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function offsetExists($offset): bool
    {
        return isset($this->key[$offset]);
    }

    /**
     * @inheritDoc
     */
    public function offsetGet(mixed $offset): mixed
    {
        return $this->make($offset);
    }

    /**
     * @inheritDoc
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->registers[$offset] = $value;
    }

    /**
     * @inheritDoc
     */
    public function offsetUnset(mixed $offset): void
    {
        unset($this->registers[$offset]);
    }
}
