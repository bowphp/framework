<?php

namespace Bow\Configuration;

use Bow\Configuration\Loader;
use Bow\Application\Actionner;
use Bow\Configuration\Configuration;

class ActionnerConfiguration extends Configuration
{
    /**
     * Configuration du service
     *
     * @param Loader $config
     * @return void
     */
    public function make(Loader $config)
    {
        $this->container->bind('actionner', function () use ($config) {
            return Actionner::configure($config->namespaces(), $config->middlewares());
        });
    }

    /**
     * Démarrage du service
     *
     * @return void
     */
    public function start()
    {
        $this->container->make('actionner');
    }
}
