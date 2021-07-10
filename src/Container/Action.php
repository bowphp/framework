<?php

namespace Bow\Container;

use Bow\Contracts\ResponseInterface;
use Bow\Database\Barry\Model;
use Bow\Http\Request;
use Bow\Router\Exception\RouterException;
use Bow\Support\Collection;
use Closure;
use InvalidArgumentException;
use ReflectionClass;
use ReflectionException;
use ReflectionFunction;

class Action
{
    /**
     * The list of namespaces defined in the application
     *
     * @var array
     */
    private $namespaces;

    /**
     * The list of middleware loads in the application
     *
     * @var array
     */
    private $middlewares;

    /**
     * The Action instance
     *
     * @var Action
     */
    private static $instance;

    /**
     * The Dispatcher instance
     *
     * @var Dispatcher
     */
    private $dispatcher;

    /**
     * Action constructor
     *
     * @param array $namespaces
     * @param array $middlewares
     */
    public function __construct(array $namespaces, array $middlewares)
    {
        $this->namespaces = $namespaces;

        $this->middlewares = $middlewares;

        $this->dispatcher = new Dispatcher;
    }

    /**
     * Action configuration
     *
     * @param array $namespaces
     * @param array $middlewares
     *
     * @return static
     */
    public static function configure(array $namespaces, array $middlewares)
    {
        if (is_null(static::$instance)) {
            static::$instance = new Action($namespaces, $middlewares);
        }

        return static::$instance;
    }

    /**
     * Retrieves Action instance
     *
     * @return Action
     */
    public static function getInstance()
    {
        return static::$instance;
    }

    /**
     * Add a middleware to the list
     *
     * @param array|callable $middlewares
     * @param bool $end
     */
    public function pushMiddleware($middlewares, $end = false)
    {
        $middlewares = (array) $middlewares;

        if ($end) {
            array_merge($this->middlewares, $middlewares);
        } else {
            array_merge($middlewares, $this->middlewares);
        }
    }

    /**
     * Adding a namespace to the list
     *
     * @param array|string $namespace
     */
    public function pushNamespace($namespace)
    {
        $namespace = (array) $namespace;

        $this->namespaces = array_merge($this->namespaces, $namespace);
    }

    /**
     * Callback launcher
     *
     * @param callable|string|array $actions
     * @param mixed $param
     *
     * @return mixed
     *
     * @throws RouterException
     * @throws ReflectionException
     */
    public function call($actions, $param = null)
    {
        $param = (array) $param;

        /**
         * We execute the action define as a string
         */
        if (is_string($actions) || is_callable($actions)) {
            $actions = [$actions];
        }

        if (!is_array($actions)) {
            throw new InvalidArgumentException(
                'The first parameter must be an array, a string or a closure.',
                E_USER_ERROR
            );
        }

        $middlewares = [];

        /**
         * We verify the existence of middleware associated with the action
         * and extracting the middleware
         */
        if (isset($actions['middleware'])) {
            $middlewares = (array) $actions['middleware'];

            unset($actions['middleware']);
        }

        /**
         * We verify if controller is associate to action
         * like [AppController::class, 'action']
         */
        if (count($actions) === 2) {
            if (class_exists($actions[0])) {
                $actions = [$actions[0] . '::' . $actions[1]];
            }
        }

        /**
         * We verify the existence of controller associated
         * with the action and extracting the controller
         */
        if (isset($actions['controller'])) {
            $actions = (array) $actions['controller'];
        }

        $functions = [];

        /**
         * We normalize of the action to execute and
         * creation of the dependency injection
         */
        foreach ($actions as $key => $action) {
            if (is_string($action)) {
                array_push($functions, $this->controller($action));

                continue;
            }

            if (! is_callable($action)) {
                continue;
            }

            if (is_array($action) && $action[0] instanceof Closure) {
                $injection = $this->injectorForClosure($action[0]);
            } else {
                $injection = $this->injectorForClosure($action);
            }

            array_push($functions, ['action' => $action, 'injection' => $injection]);
        }

        /**
         * We load the middleware associated with the action
         */
        foreach ($middlewares as $middleware) {
            if (is_callable($middleware)) {
                if ($middleware instanceof Closure || is_array($middleware)) {
                    $this->dispatcher->pipe($middleware);

                    continue;
                }
            }

            if (class_exists($middleware)) {
                $this->dispatcher->pipe($middleware);

                continue;
            }

            $parts = [];

            if (is_string($middleware)) {
                $parts = explode(':', $middleware, 2);

                $middleware = $parts[0];
            }

            // We check if middleware if define via aliases
            if (!array_key_exists($middleware, $this->middlewares)) {
                throw new RouterException(sprintf('%s is not define middleware.', $middleware), E_ERROR);
            }

            // We check if the defined middleware is a valid middleware.
            if (!class_exists($this->middlewares[$middleware])) {
                throw new RouterException(sprintf('%s is not a middleware class.', $middleware));
            }

            // We add middleware into dispatch pipeline
            $this->dispatcher->pipe(
                $this->middlewares[$middleware],
                count($parts) != 2 ? [] : explode(',', $parts[1])
            );
        }

        // We process middleware through the dispatcher
        $response = $this->dispatcher->process(
            Request::getInstance()
        );

        switch (true) {
            case is_null($response):
            case is_string($response):
            case is_array($response):
            case is_object($response):
            case $response instanceof \Iterable:
            case $response instanceof ResponseInterface:
                return $response;
            case $response instanceof Model || $response instanceof Collection:
                return $response->toArray();
        }

        return $this->dispatchControllers($functions, $param);
    }

    /**
     * Execution of define controller
     *
     * @param array $functions
     * @param array $params
     *
     * @return mixed
     */
    private function dispatchControllers(array $functions, array $params)
    {
        $response = null;

        // Fix the unparsed parameter in url
        foreach ($params as $key => $value) {
            $params[$key] = urldecode($value);
        }

        // We launch of the execution of the list of actions define
        // Function has been executed according to an order
        foreach ($functions as $function) {
            $response = call_user_func_array(
                $function['action'],
                array_merge($function['injection'], $params)
            );

            if ($response === true) {
                continue;
            }

            if ($response === false || is_null($response)) {
                return $response;
            }
        }

        return $response;
    }

    /**
     * Successively launches a function list.
     *
     * @param array|callable $arr
     * @param array|callable $arg
     *
     * @return mixed
     *
     * @throws ReflectionException
     */
    public function execute($arr, $arg)
    {
        if (is_callable($arr)) {
            return call_user_func_array($arr, $arg);
        }

        if (is_array($arr)) {
            return call_user_func_array($arr, $arg);
        }

        // We launch the controller loader if $cb is a String
        $controller = $this->controller($arr);

        if ($controller['action'][1] == null) {
            array_splice($controller['action'], 1, 1);
        }

        if (is_array($controller)) {
            return call_user_func_array(
                $controller['action'],
                array_merge($controller['injection'], $arg)
            );
        }

        return false;
    }

    /**
     * Load the controllers defined as string
     *
     * @param string $controller_name
     * @return array
     * @throws ReflectionException
     */
    public function controller($controller_name)
    {
        // Retrieving the class and method to launch.
        if (is_null($controller_name)) {
            return null;
        }

        $parts = preg_split('/::|@/', $controller_name);

        if (count($parts) == 1) {
            $parts[1] = '__invoke';
        }

        list($class, $method) = $parts;

        if (!class_exists($class, true)) {
            $class = sprintf('%s\\%s', $this->namespaces['controller'], ucfirst($class));
        }

        $injections = $this->injector($class, $method);
        $controller = (new ReflectionClass($class));
        $constructor = $controller->getConstructor();

        $controller_injections = [];
        if (!is_null($constructor)) {
            $controller_injections = $this->getInjectParameters($constructor->getParameters());
        }

        $instance = $controller->newInstanceArgs($controller_injections);

        return [
            'action' => [$instance, $method],
            'injection' => $injections
        ];
    }

    /**
     * Load the closure define as action
     *
     * @param Closure $closure
     * @return array
     */
    public function closure($closure)
    {
        // Retrieving the class and method to launch.
        if (!is_callable($closure)) {
            return null;
        }

        $injections = $this->injectorForClosure($closure);

        return [
            'action' => $closure,
            'injection' => $injections
        ];
    }

    /**
    * Make any class injection
    *
    * @param string $classname
    * @param string|null $method
    * @return array
    *
    * @throws ReflectionException
    */
    public function injector(string $classname, string $method = null)
    {
        $reflection = new ReflectionClass($classname);
        
        if (is_null($method)) {
            $method = "__invoke";
        }
        
        $parameters = $reflection->getMethod($method)->getParameters();
        
        return $this->getInjectParameters($parameters);
    }
    
    /**
    * Injection for closure
    *
    * @param callable $closure
    * @return array
    * @throws
    */
    public function injectorForClosure(callable $closure)
    {
        $reflection = new ReflectionFunction($closure);
        
        $parameters = $reflection->getParameters();
        
        return $this->getInjectParameters($parameters);
    }
    
    /**
    * Get all parameters define by user in method injectable
    *
    * @param array $parameters
    * @return array
    *
    * @throws ReflectionException
    */
    private function getInjectParameters(array $parameters)
    {
        $params = [];
        
        foreach ($parameters as $parameter) {
            $class = $parameter->getClass();
            
            if (is_null($class)) {
                continue;
            }
            
            $param = $this->getInjectParameter($class);

            if (is_null($param)) {
                continue;
            }

            $params[] = $param;
        }
        
        return $params;
    }

    /**
     * Get injectable parameter
     *
     * @param mixed $class
     * @return mixed
     */
    private function getInjectParameter($class)
    {
        $class_name = $class->getName();
            
        if (! class_exists($class_name, true)) {
            throw new InvalidArgumentException(
                sprintf('class %s not exists', $class_name)
            );
        }
        
        if (in_array(strtolower($class_name), $this->getInjectorExceptedType())) {
            return null;
        }

        if (method_exists($class_name, 'getInstance')) {
            return $class_name::getInstance();
        }

        $reflection = new ReflectionClass($class_name);
        
        $args = [];
        if ($reflection->isInstantiable() && $reflection->getConstructor()) {
            $args = $this->injector($class_name, '__construct');
        }
        
        return (new ReflectionClass($class_name))->newInstanceArgs($args);
    }

    /**
    * The list of type not allowed to injection
    *
    * @return array
    */
    private function getInjectorExceptedType()
    {
        return [
            'string', 'array', 'bool', 'int',
            'integer', 'double', 'float', 'callable',
            'object', 'stdclass', '\closure', 'closure'
        ];
    }
}
