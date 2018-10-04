<?php

namespace Bow\Configuration\Configurations;

use Bow\Application\Actionner;
use Bow\Configuration\Configuration;
use Bow\Configuration\Loader;

class ActionnerConfiguration extends Configuration
{
    /**
     * @var array
     */
    private $middlewares = [
        'trim' => \Bow\Middleware\TrimMiddleware::class,
    ];
    
    /**
     * @inheritdoc
     */
    public function create(Loader $config)
    {
        $this->container->bind('actionner', function () use ($config) {
            $middlewares = array_merge($config->middlewares(), $this->middlewares);

            return Actionner::configure($config->namespaces(), $middlewares);
        });
    }

    /**
     * @inheritdoc
     */
    public function run()
    {
        $this->container->make('actionner');
    }
}
