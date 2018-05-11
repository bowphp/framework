<?php

namespace Bow\Services;

use Bow\Config\Config;
use Bow\Application\Actionner;
use Bow\Application\Service as BowService;

class ActionnerService extends BowService
{
    /**
     * Configuration du service
     *
     * @param Config $config
     * @return void
     */
    public function make(Config $config)
    {
        $this->app->capsule(Actionner::class, function () use ($config) {
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
        $this->app->capsule(Actionner::class);
    }
}
