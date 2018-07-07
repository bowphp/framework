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
     * @inheritdoc
     */
    public function make(Config $config)
    {
        $this->app->capsule(Auth::class, function () use ($config) {
            return Auth::configure($config['auth']);
        });
    }

    /**
     * @inheritdoc
     */
    public function start()
    {
        $this->app->capsule(Auth::class);
    }
}
