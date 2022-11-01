<?php

declare(strict_types=1);

namespace Bow\Router;

use Bow\Router\Exception\RouterException;

class Router
{
    /**
     * Define the functions related to an http
     * code executed if this code is up
     *
     * @var array
     */
    protected $error_code = [];

    /**
     * Define the global middleware
     *
     * @var array
     */
    protected $middlewares = [];

    /**
     * Define the routing prefix
     *
     * @var string
     */
    protected $prefix;

    /**
     * @var string
     */
    protected $special_method;

    /**
     * Method Http current.
     *
     * @var array
     */
    protected $current = [];

    /**
     * Define the auto csrf check status.
     *
     * @var bool
     */
    protected $auto_csrf = true;

    /**
     * Route collection.
     *
     * @var array
     */
    protected static $routes = [];

    /**
     * Define the base route
     *
     * @var string
     */
    private $base_route;

    /**
     * Define the request method
     *
     * @var string
     */
    private $method;

    /**
     * Define the request _method parse to form
     * for helper router define a good method called
     *
     * @var string
     */
    private $magic_method;

    /**
     * Router constructor
     *
     * @return void
     */
    protected function __construct($method, $magic_method = null, $base_route = '', array $middlewares = [])
    {
        $this->method = $method;
        $this->magic_method = $magic_method;
        $this->middlewares = $middlewares;
        $this->base_route = $base_route;
    }

    /**
     * Set the base route
     *
     * @param string $base_route
     */
    public function setBaseRoute(string $base_route)
    {
        $this->base_route = $base_route;
    }

    /**
     * Set auto CSRF status
     * Note: Disable only you run on test env
     *
     * @param bool $auto_csrf
     */
    public function setAutoCsrf(bool $auto_csrf)
    {
        $this->auto_csrf = $auto_csrf;
    }

    /**
     * Add a prefix on the roads
     *
     * @param string $prefix
     * @param callable $cb
     * @return Router
     * @throws
     */
    public function prefix($prefix, callable $cb)
    {
        $prefix = rtrim($prefix, '/');

        if (!preg_match('@^/@', $prefix)) {
            $prefix = '/' . $prefix;
        }

        if ($this->prefix !== null) {
            $this->prefix .= $prefix;
        } else {
            $this->prefix = $prefix;
        }

        call_user_func_array($cb, [$this]);

        $this->prefix = '';

        return $this;
    }

    /**
     * Allows to associate a global middleware on an route
     *
     * @param array $middlewares
     * @return Router
     */
    public function middleware($middlewares)
    {
        $middlewares = (array) $middlewares;

        $collection = [];

        foreach ($middlewares as $middleware) {
            if (class_exists($middleware, true)) {
                $collection[] = [new $middleware, 'process'];
            } else {
                $collection[] = $middleware;
            }
        }

        return new Router($this->method, $this->magic_method, $this->base_route, $collection);
    }

    /**
     * Route mapper
     *
     * @param array $definition
     * @throws RouterException
     */
    public function route(array $definition)
    {
        if (!isset($definition['path'])) {
            throw new RouterException('The undefined path');
        }

        if (!isset($definition['method'])) {
            throw new RouterException('Unspecified method');
        }

        if (!isset($definition['handler'])) {
            throw new RouterException('Undefined controller');
        }

        $method = $definition['method'];

        $path = $definition['path'];

        $where = $definition['where'] ?? [];

        $cb = (array) $definition['handler'];

        if (isset($cb['middleware'])) {
            unset($cb['middleware']);
        }

        if (isset($cb['controller'])) {
            unset($cb['controller']);
        }

        $route = $this->pushHttpVerb($method, $path, $cb);

        if (isset($definition['middleware'])) {
            $route->middleware($definition['middleware']);
        }

        $route->where($where);
    }

    /**
     * Add a route for
     *
     * GET, POST, DELETE, PUT, OPTIONS, PATCH
     *
     * @param string $path
     * @param callable|string|array $cb
     * @return Router
     * @throws
     */
    public function any($path, $cb)
    {
        foreach (['options', 'patch', 'post', 'delete', 'put', 'get'] as $method) {
            $this->$method($path, $cb);
        }

        return $this;
    }

    /**
     * Add a GET route
     *
     * @param string $path
     * @param callable|string|array $cb
     * @return Route
     */
    public function get($path, $cb)
    {
        return $this->routeLoader('GET', $path, $cb);
    }

    /**
     * Add a POST route
     *
     * @param string $path
     * @param callable|string|array $cb
     * @return Route
     */
    public function post($path, $cb)
    {
        if (!$this->magic_method) {
            return $this->routeLoader('POST', $path, $cb);
        }

        $method = strtoupper($this->magic_method);

        if (in_array($method, ['DELETE', 'PUT'])) {
            $this->special_method = $method;
        }

        return $this->pushHttpVerb($method, $path, $cb);
    }

    /**
     * Add a DELETE route
     *
     * @param string $path
     * @param callable|string|array $cb
     * @return Route
     */
    public function delete($path, $cb)
    {
        return $this->pushHttpVerb('DELETE', $path, $cb);
    }

    /**
     * Add a PUT route
     *
     * @param string $path
     * @param callable|string|array $cb
     * @return Route
     */
    public function put($path, $cb)
    {
        return $this->pushHttpVerb('PUT', $path, $cb);
    }

    /**
     * Add a PATCH route
     *
     * @param string $path
     * @param callable|string|array $cb
     * @return Route
     */
    public function patch($path, $cb)
    {
        return $this->pushHttpVerb('PATCH', $path, $cb);
    }

    /**
     * Add a OPTIONS route
     *
     * @param string $path
     * @param callable $cb
     * @return Route
     */
    public function options($path, callable $cb)
    {
        return $this->pushHttpVerb('OPTIONS', $path, $cb);
    }

    /**
     * Launch a callback function for each HTTP error code.
     * When the define code match with response code.
     *
     * @param int $code
     * @param callable $cb
     * @return Router
     */
    public function code($code, callable $cb)
    {
        $this->error_code[$code] = $cb;

        return $this;
    }

    /**
     * Match route de tout type de method
     *
     * @param array $methods
     * @param string $path
     * @param callable|string|array $cb
     * @return Router
     */
    public function match(array $methods, $path, $cb)
    {
        foreach ($methods as $method) {
            if ($this->method == strtoupper($method)) {
                $this->pushHttpVerb(strtoupper($method), $path, $cb);
            }
        }

        return $this;
    }

    /**
     * Add other HTTP verbs [PUT, DELETE, UPDATE, HEAD, PATCH]
     *
     * @param string $method
     * @param string $path
     * @param callable|array|string $cb
     * @return Route
     */
    private function pushHttpVerb($method, $path, $cb)
    {
        if ($this->magic_method) {
            if ($this->magic_method === $method) {
                $method = $this->magic_method;
            }
        }

        return $this->routeLoader($method, $path, $cb);
    }

    /**
     * Start loading a route.
     *
     * @param string $method
     * @param string $path
     * @param Callable|string|array $cb
     * @return Route
     */
    private function routeLoader($method, $path, $cb)
    {
        $path = '/' . trim($path, '/');

        // We build the original path based on the Router loader
        $path = $this->base_route . $this->prefix . $path;

        // We define the current route and current method
        $this->current = ['path' => $path, 'method' => $method];

        // We add the new route
        $route = new Route($path, $cb);

        $route->middleware($this->middlewares);

        static::$routes[$method][] = $route;

        if (app_env('APP_ENV') != 'production' && $this->auto_csrf === true) {
            if (in_array($method, ['POST', 'DELETE', 'PUT'])) {
                $route->middleware('csrf');
            }
        }

        return $route;
    }

    /**
     * Retrieve the define special method
     *
     * @return string
     */
    protected function getSpecialMethod()
    {
        return $this->special_method;
    }

    /**
     * Check user define the special method
     *
     * @return bool
     */
    protected function hasSpecialMethod()
    {
        return !is_null($this->special_method);
    }

    /**
     * Get the route collection
     *
     * @return array
     */
    public function getRoutes()
    {
        return static::$routes;
    }
}
