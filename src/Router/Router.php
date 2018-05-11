<?php

namespace Bow\Router;

use Bow\Router\Route\Collection as RouteCollection;

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
     * @var string
     */
    private $namespace;

    /**
     * Router constructor.
     *
     * @param $config
     * @param RouteCollection $collection
     */
    public function __construct($config, RouteCollection $collection)
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
     *
     * @return Route
     */
    public function any($path, callable $cb)
    {
        foreach (['options', 'patch', 'post', 'delete', 'put', 'get'] as $method) {
            $this->$method($path, $cb);
        }

        return $this;
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
     * code, Lance une fonction en fonction du code d'erreur HTTP
     *
     * @param  int      $code
     * @param  callable $cb
     * @return Route
     */
    public function code($code, callable $cb)
    {
        $this->statusRoutes[$code] = $cb;
        return $this;
    }

    /**
     * match, route de tout type de method
     *
     * @param  array    $methods
     * @param  string   $path
     * @param  callable $cb
     * @return Route
     */
    public function match(array $methods, $path, callable $cb = null)
    {
        foreach ($methods as $method) {
            $this->pushRoute(strtoupper($method), $path, $cb);
        }

        return $this;
    }

    /**
     * mount, ajoute un branchement.
     *
     * @param  string   $branch
     * @param  callable $cb
     * @throws \Bow\Router\Exception\RouterException
     * @return Route
     */
    public function group($branch, callable $cb)
    {
        $branch = rtrim($branch, '/');

        if (!preg_match('@^/@', $branch)) {
            $branch = '/' . $branch;
        }

        if ($this->branch !== null) {
            $this->branch .= $branch;
        } else {
            $this->branch = $branch;
        }

        call_user_func_array($cb, [$this]);

        $this->branch = '';
        $this->globale_middleware = [];

        return $this;
    }

    /**
     * Push new route
     * 
     * @param string $method
     * @param string $path
     * @param mixed $cb
     */
    private function pushRoute($method, $path, $cb)
    {
        $route = new Route($path, $cb);
        $this->collection->push($route);
    }
}
