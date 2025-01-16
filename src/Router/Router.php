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
    protected array $error_code = [];

    /**
     * Define the global middleware
     *
     * @var array
     */
    protected array $middlewares = [];

    /**
     * Define the routing prefix
     *
     * @var string
     */
    protected string $prefix = '';

    /**
     * @var ?string
     */
    protected ?string $special_method = null;

    /**
     * Method Http current.
     *
     * @var array
     */
    protected array $current = [];

    /**
     * Define the auto csrf check status.
     *
     * @var bool
     */
    protected bool $auto_csrf = true;

    /**
     * Route collection.
     *
     * @var array
     */
    protected static array $routes = [];

    /**
     * Define the base route
     *
     * @var string
     */
    private string $base_route;

    /**
     * Define the request method
     *
     * @var string
     */
    private string $method;

    /**
     * Define the request _method parse to form
     * for helper router define a good method called
     *
     * @var ?string
     */
    private ?string $magic_method;

    /**
     * Router constructor
     *
     * @param string $method
     * @param ?string $magic_method
     * @param string $base_route
     * @param array $middlewares
     */
    protected function __construct(
        string $method,
        ?string $magic_method = null,
        string $base_route = '',
        array $middlewares = []
    ) {
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
    public function setBaseRoute(string $base_route): void
    {
        $this->base_route = $base_route;
    }

    /**
     * Set auto CSRF status
     * Note: Disable only you run on test env
     *
     * @param bool $auto_csrf
     * @return void
     */
    public function setAutoCsrf(bool $auto_csrf): void
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
    public function prefix(string $prefix, callable $cb): Router
    {
        $prefix = rtrim($prefix, '/');

        if (!preg_match('@^/@', $prefix)) {
            $prefix = '/' . $prefix;
        }

        $this->prefix .= $prefix;

        call_user_func_array($cb, [$this]);

        $this->prefix = '';

        return $this;
    }

    /**
     * Allows to associate a global middleware on an route
     *
     * @param array|string $middlewares
     * @return Router
     */
    public function middleware(array|string $middlewares): Router
    {
        $middlewares = (array) $middlewares;

        $collection = [];

        foreach ($middlewares as $middleware) {
            $collection[] = class_exists($middleware, true) ? [new $middleware(), 'process'] : $middleware;
        }

        return new Router($this->method, $this->magic_method, $this->base_route, $collection);
    }

    /**
     * Route mapper
     *
     * @param array $definition
     * @throws RouterException
     */
    public function route(array $definition): void
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
     * @return Route
     * @throws
     */
    public function any(string $path, callable|string|array $cb): Route
    {
        $methods = array_map('strtoupper', ['options', 'patch', 'post', 'delete', 'put', 'get']);

        return $this->pushHttpVerb($methods, $path, $cb);
    }

    /**
     * Add a GET route
     *
     * @param string $path
     * @param callable|string|array $cb
     * @return Route
     */
    public function get(string $path, callable|string|array $cb): Route
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
    public function post(string $path, callable|string|array $cb): Route
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
    public function delete(string $path, callable|string|array $cb): Route
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
    public function put(string $path, callable|string|array $cb): Route
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
    public function patch(string $path, callable|string|array $cb): Route
    {
        return $this->pushHttpVerb('PATCH', $path, $cb);
    }

    /**
     * Add a OPTIONS route
     *
     * @param string $path
     * @param callable|string|array $cb
     * @return Route
     */
    public function options(string $path, callable|string|array $cb): Route
    {
        return $this->pushHttpVerb('OPTIONS', $path, $cb);
    }

    /**
     * Launch a callback function for each HTTP error code.
     * When the define code match with response code.
     *
     * @param int $code
     * @param callable|array|string $cb
     * @return Router
     */
    public function code(int $code, callable|array|string $cb): Router
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
     * @return Route
     */
    public function match(array $methods, string $path, callable|string|array $cb): Route
    {
        $methods = array_map('strtoupper', $methods);

        return $this->pushHttpVerb($methods, $path, $cb);
    }

    /**
     * Add other HTTP verbs [PUT, DELETE, UPDATE, HEAD, PATCH]
     *
     * @param string|array $methods
     * @param string $path
     * @param callable|array|string $cb
     * @return Route
     */
    private function pushHttpVerb(string|array $methods, string $path, callable|string|array $cb): Route
    {
        $methods = (array) $methods;

        if (!$this->magic_method) {
            return $this->routeLoader($methods, $path, $cb);
        }

        foreach ($methods as $key => $method) {
            if ($this->magic_method === $method) {
                $methods[$key] = $this->magic_method;
            }
        }

        return $this->routeLoader($methods, $path, $cb);
    }

    /**
     * Start loading a route.
     *
     * @param string|array $methods
     * @param string $path
     * @param callable|string|array $cb
     * @return Route
     */
    private function routeLoader(string|array $methods, string $path, callable|string|array $cb): Route
    {
        $methods = (array) $methods;

        $path = '/' . trim($path, '/');

        // We build the original path based on the Router loader
        $path = $this->base_route . $this->prefix . $path;

        // We add the new route
        $route = new Route($path, $cb);

        $route->middleware($this->middlewares);

        foreach ($methods as $method) {
            static::$routes[$method][] = $route;

            // We define the current route and current method
            $this->current = ['path' => $path, 'method' => $method];

            if (
                $this->auto_csrf === true
                && in_array($method, ['POST', 'DELETE', 'PUT'])
            ) {
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
    protected function getSpecialMethod(): string
    {
        return $this->special_method;
    }

    /**
     * Check user define the special method
     *
     * @return bool
     */
    protected function hasSpecialMethod(): bool
    {
        return !is_null($this->special_method);
    }

    /**
     * Get the route collection
     *
     * @return array
     */
    public function getRoutes(): array
    {
        return static::$routes;
    }
}
