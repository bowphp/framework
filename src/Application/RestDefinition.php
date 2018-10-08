<?php

namespace Bow\Application;

class RestDefinition
{
    /**
     * Construire les urls du système REST de Bow
     *
     * @return array
     */
    public static function definitions()
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
