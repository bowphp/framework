<?php

namespace Bow\Configuration\Configurations;

use Bow\Configuration\Loader;
use Bow\Application\Actionner;
use Bow\Configuration\Configuration;

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
