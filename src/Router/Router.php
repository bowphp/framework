<?php

namespace Bow\Router;

use Bow\Config\Config;
use Bow\Router\Collection as RouteCollection;

class Router
{
    /**
     * @var string
     */
    private $config;

    /**
     * @var RouteCollection
     */
    private $collection;

    /**
     * @var array
     */
    private $globale_middleware;

    /**
     * @var string
     */
    private $prefix;

    /**
     * @var string
     */
    private $namespace;

    /**
     * Router constructor.
     *
     * @param $config
     * @param RouteCollection $collection
     */
    public function __construct(Config $config, RouteCollection $collection)
    {
        $this->config = $config;

        $this->namespace = $config->namespaces();

        $this->collection = $collection;
    }

    /**
     * @return RouteCollection
     */
    public function getCollection()
    {
        return $this->collection;
    }


    /**
     * get, route de type GET ou bien retourne les variable ajoutés dans Bow
     *
     * @param string         $path
     * @param callable|array $cb
     *
     * @return Route
     */
    public function get($path, $cb)
    {
        return $this->pushRoute('GET', $path, $cb);
    }

    /**
     * post, route de type POST
     *
     * @param string   $path
     * @param callable $cb
     *
     * @return Route
     */
    public function post($path, $cb)
    {
        return $this->pushRoute('POST', $path, $cb);
    }

    /**
     * any, route de tout type GET|POST|DELETE|PUT|OPTIONS|PATCH
     *
     * @param string   $path
     * @param Callable $cb
     */
    public function any($path, callable $cb)
    {
        foreach (['options', 'patch', 'post', 'delete', 'put', 'get'] as $method) {
            $this->$method($path, $cb);
        }
    }

    /**
     * delete, route de tout type DELETE
     *
     * @param string   $path
     * @param callable $cb
     *
     * @return Route
     */
    public function delete($path, $cb)
    {
        return $this->pushRoute('DELETE', $path, $cb);
    }

    /**
     * put, route de tout type PUT
     *
     * @param string   $path
     * @param callable $cb
     *
     * @return Route
     */
    public function put($path, $cb)
    {
        return $this->pushRoute('PUT', $path, $cb);
    }

    /**
     * patch, route de tout type PATCH
     *
     * @param string   $path
     * @param callable $cb
     *
     * @return Route
     */
    public function patch($path, $cb)
    {
        return $this->pushRoute('PATCH', $path, $cb);
    }

    /**
     * patch, route de tout type PATCH
     *
     * @param  string   $path
     * @param  callable $cb
     * @return Route
     */
    public function options($path, callable $cb)
    {
        return $this->pushRoute('OPTIONS', $path, $cb);
    }

    /**
     * match, route de tout type de method
     *
     * @param  array    $methods
     * @param  string   $path
     * @param  callable $cb
     */
    public function match(array $methods, $path, callable $cb = null)
    {
        foreach ($methods as $method) {
            $this->pushRoute(strtoupper($method), $path, $cb);
        }
    }

    /**
     * mount, ajoute un branchement.
     *
     * @param  string $prefix
     * @param  callable $cb
     * @return Router
     */
    public function group($prefix, callable $cb)
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

        $this->globale_middleware = [];

        return $this;
    }

    /**
     * Push new route
     *
     * @param string $method
     * @param string $path
     * @param mixed $cb
     * @return Route;
     */
    private function pushRoute($method, $path, $cb)
    {
        $route = new Route($path, $cb);

        $this->collection->in($method)->push($route);

        return $route;
    }
}
