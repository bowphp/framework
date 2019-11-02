<?php

namespace Bow\Router;

use Bow\Router\Exception\RouterException;
use Bow\Support\Capsule;

class Router
{
    /**
     * Define the functions related to an http
     * code executed if this code is up
     *
     * @var array
     */
    private $error_code = [];

    /**
     * Define the gloal middleware
     *
     * @var array
     */
    private $middlewares = [];

    /**
     * The routing prefixer
     *
     * @var string
     */
    private $prefix;

    /**
     * @var string
     */
    private $special_method;

    /**
     * Method Http courrante.
     *
     * @var array
     */
    private $current = [];

    /**
     * Route collection.
     *
     * @var array
     */
    private $routes = [];

    /**
     * The HTTP Request
     *
     * @var Request
     */
    private $request;

    /**
     * Router constructor
     *
     * @param Capsule $app
     * @return void
     */
    private function __construct(Capsule $app)
    {
        $this->app = $app;
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

        $this->middlewares = [];

        foreach ($middlewares as $middleware) {
            if (is_callable($middleware)) {
                $this->middlewares[] = $middleware;
            } elseif (class_exists($middleware, true)) {
                $this->middlewares[] = [new $middleware, 'process'];
            } else {
                $this->middlewares[] = $middleware;
            }
        }

        return $this;
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
            throw new RouterException('The path is undefined');
        }

        if (!isset($definition['method'])) {
            throw new RouterException('Http method is unspecified');
        }

        if (!isset($definition['handler'])) {
            throw new RouterException('Hanlder is undefined');
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

        $route = $this->pushHttpVerbe($method, $path, $cb);

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
        $input = $this->request;

        if (!$input->has('_method')) {
            return $this->routeLoader('POST', $path, $cb);
        }

        $method = strtoupper($input->get('_method'));

        if (in_array($method, ['DELETE', 'PUT'])) {
            $this->special_method = $method;
        }

        return $this->pushHttpVerbe($method, $path, $cb);
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
        return $this->pushHttpVerbe('DELETE', $path, $cb);
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
        return $this->pushHttpVerbe('PUT', $path, $cb);
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
        return $this->pushHttpVerbe('PATCH', $path, $cb);
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
        return $this->pushHttpVerbe('OPTIONS', $path, $cb);
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
            if ($this->request->method() === strtoupper($method)) {
                $this->pushHttpVerbe(strtoupper($method), $path, $cb);
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
    private function pushHttpVerbe($method, $path, $cb)
    {
        $input = $this->request;

        if ($input->has('_method')) {
            if ($input->get('_method') === $method) {
                $method = $input->get('_method');
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
        // We build the original path based on the Router loader
        $path = $this->app->getConfig('app.root').$this->prefix.$path;

        // We define the current route and current method
        $this->current = ['path' => $path, 'method' => $method];

        // We add the new route
        $route = new Route($path, $cb);

        $route->middleware($this->middlewares);

        $this->routes[$method][] = $route;

        $route->middleware('trim');

        if (in_array($method, ['POST', 'DELETE', 'PUT'])) {
            $route->middleware('csrf');
        }

        return $route;
    }

    /**
     * Get the all defined route
     *
     * @return array
     */
    public function getRoutes()
    {
        return $this->routes;
    }
}
