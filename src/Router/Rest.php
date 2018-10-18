<?php

namespace Bow\Router;

use Bow\Application\Application;
use Bow\Support\Capsule;

class Rest
{
    /**
     * Application instance
     *
     * @var Application
     */
    private static $application;

    /**
     * The define routing list
     *
     * @var array
     */
    private static $routes = [
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
        ],
        [
            'url'    => '/:id/edit',
            'call'   => 'edit',
            'method' => 'get'
        ],
        [
            'url'    => '/create',
            'call'   => 'create',
            'method' => 'get'
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
    public static function make($url, $controller, array $where = [], array $ignore_method = [])
    {
        static::$application = Capsule::getInstance()->make('app');

        /**
         * Association des routes
         */
        foreach (static::$routes as $key => $route) {
            /**
             * On vérifie si la methode à appelé est ignoré
             */
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
     * @param array $where
     * @param array $ignore_method
     */
    private static function bind($url, $controller, array $definition, array $where)
    {
        $path = '/'.trim($url.$definition['url'], '/');

        // Lancement de la methode de mapping de route.
        $route = static::$application->{$definition['method']}(
            $path,
            sprintf("%s@%s", $controller, $definition['call'])
        );

        // Ajout de nom sur la route
        $name = str_replace('/', '.', $url).'.'.$definition['call'];

        $route->name($name);

        // Association des critères définies
        if (isset($where[$definition['call']])) {
            $route->where((array) $where[$definition['call']]);
        } else {
            $route->where((array) $where);
        }
    }
}
