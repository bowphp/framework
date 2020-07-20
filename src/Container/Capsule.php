<?php

namespace Bow\Container;

use ArrayAccess;
use Closure;
use InvalidArgumentException;
use ReflectionClass;
use ReflectionException;
use ReflectionFunction;

class Capsule implements ArrayAccess
{
    /**
    * The container register for bind by alias
    *
    * @var array
    */
    private $registers = [];
    
    /**
    * The container register for instance
    *
    * @var array
    */
    private $instances = [];
    
    /**
    * The container factory maker
    *
    * @var array
    */
    private $factories = [];
    
    /**
    * Represents a cache collector
    *
    * @var array
    */
    private $key = [];
    
    /**
    * Represents the compilation parameters
    *
    * @var array
    */
    private $parameters = [];
    
    /**
    * Represents the instance of Capsule
    *
    * @var Capsule
    */
    private static $instance;
    
    /**
    * Get instance of Capsule
    *
    * @return Capsule
    */
    public static function getInstance()
    {
        if (is_null(static::$instance)) {
            static::$instance = new static;
        }
        
        return static::$instance;
    }
    
    /**
    * Make the
    *
    * @param string $key
    *
    * @return mixed
    * @throws
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
            return  $this->instances[$key] = $this->registers[$key]();
        }
        
        return null;
    }
    
    /**
    * Compilation with parameter
    *
    * @param mixed $key
    * @param array $parameters
    * @return mixed
    * @throws
    */
    public function makeWith($key, $parameters = [])
    {
        $this->parameters = $parameters;
        
        $resolved = $this->resolve($key);
        
        $this->parameters = [];
        
        return $resolved;
    }
    
    /**
    * Add to register
    *
    * @param string $key
    *
    * @param mixed  $value
    */
    public function bind($key, $value)
    {
        $this->key[$key] = true;
        
        $this[$key] = $value;
    }
    
    /**
    * Register the instance of a class
    *
    * @param string $key
    * @param Closure $value
    *
    * @return void
    */
    public function factory($key, \Closure $value)
    {
        $this->factories[$key] = $value;
    }
    
    /**
    * Saves the instance of a class
    *
    * @param string   $key
    * @param mixed $instance
    *
    * @return void
    */
    public function instance($key, $instance)
    {
        if (!is_object($instance)) {
            throw new InvalidArgumentException('Parameter [2] is invalid');
        }
        
        $this->instances[$key] = $instance;
    }
    
    /**
    * Instantiate a class by its key
    *
    * @param string $key
    *
    * @return mixed
    * @throws
    */
    private function resolve($key)
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
            if ($parameter->getClass()) {
                $parameters_lists[] = $this->make($parameter->getClass()->getName());
            } else {
                $parameters_lists[] = $parameter->getDefaultValue();
            }
        }
        
        if (!empty($this->parameters)) {
            $parameters_lists = $this->parameters;
            
            $this->parameters = [];
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
