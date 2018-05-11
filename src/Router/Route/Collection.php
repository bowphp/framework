<?php

namespace Bow\Router\Route;

class Collection
{
    /**
     * @var array
     */
    private $collection;

    /**
     * @var array
     */
    private $error_code;

    /**
     * @var string
     */
    private $method;

    /**
     * RouterCollection constructor.
     *
     * @param $method
     */
    public function __construct($method)
    {
        $this->method = $method;
    }

    /**
     * Add new route
     *
     * @param Route $route
     */
    public function push(Route $route)
    {
        $this->collection[] = $route;
    }

    /**
     * Retourne la listes des routes de l'application
     *
     * @return array
     */
    public function getRoutes()
    {
        return $this->collection;
    }

    /**
     * Lanceur de l'application
     *
     * @return mixed
     * @throws \Bow\Router\Exception\RouterException
     */
    public function run()
    {
        //
    }
}
