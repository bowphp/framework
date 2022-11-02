<?php

declare(strict_types=1);

namespace Bow\Router;

use Bow\Application\Application;
use Bow\Container\Capsule;

class Resource
{
    /**
     * Application instance
     *
     * @var Application
     */
    private static Application $application;

    /**
     * The define routing list
     *
     * @var array
     */
    private static array $routes = [
        [
            'url'    => '/',
            'call'   => 'index',
            'method' => 'get'
        ],
        [
            'url'    => '/',
            'call'   => 'store',
            'method' => 'post'
        ],
        [
            'url'    => '/:id',
            'call'   => 'show',
            'method' => 'get'
        ],
        [
            'url'    => '/:id',
            'call'   => 'update',
            'method' => 'put'
        ],
        [
            'url'    => '/:id',
            'call'   => 'destroy',
            'method' => 'delete'
        ]
    ];

    /**
     * Make rest
     *
     * @param string $url
     * @param mixed $controller
     * @param array $where
     * @param array $ignore_method
     */
    public static function make(string $url, mixed $controller, array $where = [], array $ignore_method = []): void
    {
        static::$application = Capsule::getInstance()->make('app');

        // Route Association
        foreach (static::$routes as $key => $route) {
            // We check if the method to be called is ignored
            if (!in_array($route['call'], $ignore_method)) {
                static::bind($url, $controller, $route, $where);
            }
        }
    }

    /**
     * Bind routing
     *
     * @param string $url
     * @param mixed $controller
     * @param array $definition
     * @param array $where
     * @throws
     */
    private static function bind(string $url, mixed $controller, array $definition, array $where): void
    {
        $path = '/'.trim($url.$definition['url'], '/');

        // Launch of the route mapping method.
        $route = static::$application->{$definition['method']}(
            $path,
            sprintf("%s@%s", $controller, $definition['call'])
        );

        // Add name on the road
        $name = str_replace('/', '.', $url).'.'.$definition['call'];

        $route->name($name);

        // Association of defined criteria
        if (isset($where[$definition['call']])) {
            $route->where((array) $where[$definition['call']]);
        } else {
            $route->where((array) $where);
        }
    }
}
