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
     * @return string
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @return RouteCollection
     */
    public function getCollection()
    {
        return $this->collection;
    }
}
