<?php

namespace Bow\Router\Route;

class Route
{
    /**
     * @var array
     */
    private $where;

    /**
     * @var string
     */
    private $name;

    /**
     * @var array
     */
    private $middleware;

    /**
     * @var array
     */
    private $statusRoutes;

    /**
     * @var string
     */
    private $method;

    /**
     * @var string
     */
    private $action;

    /**
     * @var string
     */
    private $path;

    /**
     * Permet de donner des noms au url.
     *
     * @param  $name
     * @return Route
     */
    public function name($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Permet d'associer un middleware sur une url
     *
     * @param  array $middleware
     * @return Route
     */
    public function middleware($middleware = [])
    {
        $this->middleware = (array) $middleware;
        return $this;
    }

    /**
     * Lance une personnalisation de route.
     *
     * @param array|string $var
     * @param string       $regexContrainte
     *
     * @return Route
     */
    public function where($var, $regexContrainte = null)
    {
        if (is_array($var)) {
            $this->where = $var;
        } else {
            $this->where = [$var => $regexContrainte];
        }

        return $this;
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
        return $this->routeLoader('GET', $path, $cb);
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
        return $this->routeLoader('POST', $path, $cb);
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
        return $this->addHttpVerbe('DELETE', $path, $cb);
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
        return $this->addHttpVerbe('PUT', $path, $cb);
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
        return $this->addHttpVerbe('PATCH', $path, $cb);
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
        return $this->addHttpVerbe('OPTIONS', $path, $cb);
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
            $this->routeLoader(strtoupper($method), $path, $cb);
        }

        return $this;
    }

    /**
     * addHttpVerbe, permet d'ajouter les autres verbes http
     * [PUT, DELETE, UPDATE, HEAD, PATCH]
     *
     * @param string         $method
     * @param string         $path
     * @param callable|array $cb
     *
     * @return Route
     */
    private function addHttpVerbe($method, $path, $cb)
    {
        return $this->routeLoader($method, $path, $cb);
    }

    /**
     * routeLoader, lance le chargement d'une route.
     *
     * @param string         $method La methode HTTP
     * @param string         $path
     * @param Callable|array $cb
     *
     * @return Route
     */
    private function routeLoader($method, $path, $cb)
    {
        if (!preg_match('@^/@', $path)) {
            $path = '/' . $path;
        }

        $this->method = $method;

        $this->path = $path;

        $this->action = $cb;

        return $this;
    }

    /**
     * Get the route action
     *
     * @return string
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * Get the route action
     *
     * @return array
     */
    public function getMiddleware()
    {
        return $this->middleware;
    }

    /**
     * Get the route action
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Get the route action
     *
     * @return string
     */
    public function call()
    {
        return $this->name;
    }
}
