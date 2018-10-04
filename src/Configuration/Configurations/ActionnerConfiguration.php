<?php

namespace Bow\Configuration\Configurations;

use Bow\Application\Actionner;
use Bow\Configuration\Configuration;
use Bow\Configuration\Loader;

class ActionnerConfiguration extends Configuration
{
    /**
     * @inheritdoc
     */
    public function create(Loader $config)
    {
        $this->container->bind('actionner', function () use ($config) {
            return Actionner::configure($config->namespaces(), $config->middlewares());
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
