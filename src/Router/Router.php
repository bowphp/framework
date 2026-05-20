<?php

declare(strict_types=1);

namespace Bow\Router;

use Bow\Router\Exception\RouterException;

class Router
{
    /**
     * Route collection.
     *
     * @var array
     */
    protected static array $routes = [];

    /**
     * Define the functions related to a http
     * code executed if this code is up
     *
     * @var array
     */
    protected array $error_codes = [];

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
     * Define the domain constraint for routes
     *
     * @var string|null
     */
    protected ?string $domain = null;

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
     * Define the base route
     *
     * @var string
     */
    private string $base_route;

    /**
     * Define the request _method parse to form
     * for helper router define a good method called
     *
     * @var ?string
     */
    private ?string $magic_method;

    /**
     * Define the instance of router
     *
     * @var ?Router
     */
    private static ?Router $instance = null;

    /**
     * Router constructor
     *
     * @param ?string $magic_method
     * @param string  $base_route
     * @param array   $middlewares
     */
    protected function __construct(
        ?string $magic_method = null,
        string $base_route = '',
        array $middlewares = []
    ) {
        $this->magic_method = $magic_method;
        $this->middlewares = $middlewares;
        $this->base_route = $base_route;
    }

    /**
     * Configure route singleton instance
     *
     * @param string|null $magic_method
     * @param string $base_route
     * @param array $middlewares
     * @return Router
     */
    public static function configure(
        ?string $magic_method = null,
        string $base_route = '',
        array $middlewares = []
    ): Router {
        static::$instance = new static($magic_method, $base_route, $middlewares);

        return static::$instance;
    }

    /**
     * Get the instance of router
     *
     * @return ?Router
     */
    public static function getInstance(): ?Router
    {
        if (!static::$instance) {
            static::$instance = new static();
        }

        return static::$instance;
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
     * @param  bool $auto_csrf
     * @return void
     */
    public function setAutoCsrf(bool $auto_csrf): void
    {
        $this->auto_csrf = $auto_csrf;
    }

    /**
     * Set prefix
     *
     * @param  string $prefix
     * @return void
     */
    public function setPrefix(string $prefix): void
    {
        $this->prefix = $prefix;
    }

    /**
     * Add a prefix on the roads
     *
     * @param  string   $prefix
     * @param  callable $cb
     * @return Router
     * @throws RouterException
     */
    public function prefix(string $prefix, callable $cb): Router
    {
        $prefix = rtrim($prefix, '/');

        if (!str_starts_with($prefix, '/')) {
            $prefix = '/' . $prefix;
        }

        $this->prefix .= $prefix;

        call_user_func_array($cb, [$this]);

        $this->prefix = '';

        return $this;
    }

    /**
     * Add a domain constraint for a group of routes
     *
     * @param string $domain_pattern
     * @param callable $cb
     * @return Router
     * @throws RouterException
     */
    public function domain(string $domain_pattern, callable $cb): Router
    {
        $this->domain = $domain_pattern;

        call_user_func_array($cb, [$this]);

        $this->domain = null;

        return $this;
    }

    /**
     * Route mapper
     *
     * @param  array $definition
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

        $route = $this->pushMany($method, $path, $cb);

        if (isset($definition['middleware'])) {
            $route->middleware($definition['middleware']);
        }

        if (isset($definition['domain'])) {
            $route->withDomain($definition['domain']);
        }

        $route->where($where);
    }

    /**
     * Add other HTTP verbs [PUT, DELETE, OPTIONS, HEAD, PATCH]
     *
     * @param  string|array          $methods
     * @param  string                $path
     * @param  callable|array|string $cb
     * @return Route
     */
    private function pushMany(string|array $methods, string $path, callable|string|array $cb): Route
    {
        $methods = (array) $methods;

        foreach ($methods as $key => $method) {
            if (in_array($this->magic_method, ['PUT', 'DELETE', 'PATCH']) && in_array($method, ['PUT', 'DELETE', 'PATCH'])) {
                $methods[$key] = 'POST';
            }
        }

        return $this->push($methods, $path, $cb);
    }

    /**
     * Start loading a route.
     *
     * @param  string|array          $methods
     * @param  string                $path
     * @param  callable|string|array $cb
     * @return Route
     */
    private function push(string|array $methods, string $path, callable|string|array $cb): Route
    {
        $methods = (array) $methods;

        $path = '/' . trim($path, '/');

        // We build the original path based on the Router loader
        $path = $this->base_route . $this->prefix . $path;

        // We add the new route
        $route = new Route($path, $cb);

        if ($this->domain) {
            $route->withDomain($this->domain);
        }

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
     * Allows to associate a global middleware on a route
     *
     * @param  array|string $middlewares
     * @return Router
     */
    public function middleware(array|string $middlewares): Router
    {
        $middlewares = (array) $middlewares;

        $collection = [];

        foreach ($middlewares as $middleware) {
            $collection[] = class_exists($middleware) ? [new $middleware(), 'process'] : $middleware;
        }

        return new Router($this->magic_method, $this->base_route, $collection);
    }

    /**
     * Add a route for
     *
     * GET, POST, DELETE, PUT, OPTIONS, PATCH
     *
     * @param  string                $path
     * @param  callable|string|array $cb
     * @return Route
     * @throws
     */
    public function any(string $path, callable|string|array $cb): Route
    {
        $methods = array_map('strtoupper', ['options', 'patch', 'post', 'delete', 'put', 'get']);

        return $this->pushMany($methods, $path, $cb);
    }

    /**
     * Add a GET route
     *
     * @param  string                $path
     * @param  callable|string|array $cb
     * @return Route
     */
    public function get(string $path, callable|string|array $cb): Route
    {
        return $this->push('GET', $path, $cb);
    }

    /**
     * Add a POST route
     *
     * @param  string                $path
     * @param  callable|string|array $cb
     * @return Route
     */
    public function post(string $path, callable|string|array $cb): Route
    {
        return $this->push('POST', $path, $cb);
    }

    /**
     * Add a DELETE route
     *
     * @param  string                $path
     * @param  callable|string|array $cb
     * @return Route
     */
    public function delete(string $path, callable|string|array $cb): Route
    {
        if ($this->magic_method && strtoupper($this->magic_method) === 'DELETE') {
            return $this->post($path, $cb);
        }

        return $this->push('DELETE', $path, $cb);
    }

    /**
     * Add a PUT route
     *
     * @param  string                $path
     * @param  callable|string|array $cb
     * @return Route
     */
    public function put(string $path, callable|string|array $cb): Route
    {
        if ($this->magic_method && strtoupper($this->magic_method) === 'PUT') {
            return $this->post($path, $cb);
        }

        return $this->push('PUT', $path, $cb);
    }

    /**
     * Add a PATCH route
     *
     * @param  string                $path
     * @param  callable|string|array $cb
     * @return Route
     */
    public function patch(string $path, callable|string|array $cb): Route
    {
        if ($this->magic_method && strtoupper($this->magic_method) === 'PATCH') {
            return $this->post($path, $cb);
        }

        return $this->push('PATCH', $path, $cb);
    }

    /**
     * Add a OPTIONS route
     *
     * @param  string                $path
     * @param  callable|string|array $cb
     * @return Route
     */
    public function options(string $path, callable|string|array $cb): Route
    {
        return $this->push('OPTIONS', $path, $cb);
    }

    /**
     * Launch a callback function for each HTTP error code.
     * When the define code match with response code.
     *
     * @param  int                   $code
     * @param  callable|array|string $cb
     * @return Router
     */
    public function code(int $code, callable|array|string $cb): Router
    {
        $this->error_codes[$code] = $cb;

        return $this;
    }

    /**
     * Get the error codes
     *
     * @return array
     */
    public function getErrorCodes(): array
    {
        return $this->error_codes;
    }

    /**
     * Match route de tout type de method
     *
     * @param  array                 $methods
     * @param  string                $path
     * @param  callable|string|array $cb
     * @return Route
     */
    public function match(array $methods, string $path, callable|string|array $cb): Route
    {
        $methods = array_map('strtoupper', $methods);

        return $this->pushMany($methods, $path, $cb);
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

    /**
     * Retrieve the define special method
     *
     * @return string
     */
    public function getSpecialMethod(): string
    {
        return $this->magic_method;
    }

    /**
     * Check user define the special method
     *
     * @return bool
     */
    public function hasSpecialMethod(): bool
    {
        return !is_null($this->magic_method);
    }

    /**
     * Set the current path
     *
     * @return void
     */
    public function setCurrentPath(string $path): void
    {
        $this->current['path'] = $path;
    }

    /**
     * Register routes from controller classes
     *
     * @param string|array $controllers
     * @return Router
     */
    public function register(string|array $controllers): Router
    {
        $registrar = new AttributeRouteRegistrar($this);
        $registrar->register($controllers);

        return $this;
    }
}
