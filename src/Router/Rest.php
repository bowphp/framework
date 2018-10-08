<?php

namespace Bow\Router;

use Bow\Application\Application;

class Rest
{
    /**
     * Make rest roite mapping
     *
     * @param Application $application
     * @param string $url
     * @param mixed $controller
     * @param array $where
     */
    public static function make(Application $application, $url, $controller, array $where)
    {
        $path = '/'.trim($url.$value['url'], '/');

        // Lancement de la methode de mapping de route.
        $route = $application->{$value['method']}(
            $path,
            sprintf("%s@%s", $controller, $value['call'])
        );

        // Ajout de nom sur la route
        $name = str_replace('/', '.', $url).'.'.$value['call'];

        $route->name($name);

        // Association des critères définies
        if (isset($where[$value['call']])) {
            $route->where((array) $where[$value['call']]);
        } else {
            $route->where((array) $where);
        }
    }

    /**
     * Construire les urls du système REST de Bow
     *
     * @return array
     */
    public static function routing()
    {
        return [
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
    }
}
