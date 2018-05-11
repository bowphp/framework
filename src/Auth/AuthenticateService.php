<?php

namespace Bow\Auth;

use Bow\Auth\Auth;
use Bow\Config\Config;
use Bow\Application\Service as BowService;

class AuthenticateService extends BowService
{
    /**
     * Configuration du service
     *
     * @param Config $config
     * @return void
     */
    public function make(Config $config)
    {
        $this->app->capsule(Auth::class, function () use ($config) {
            return Auth::configure($config['auth']);
        });
    }

    /**
     * Démarrage du service
     *
     * @return void
     */
    public function start()
    {
        $this->app->capsule(Auth::class);
    }
}
