<?php

namespace Bow\Services;

use Bow\Support\Env;
use Bow\Config\Config;
use Bow\Application\Service as BowService;

class EnvService extends BowService
{
    /**
     * @inheritdoc
     * @throws
     */
    public function make(Config $config)
    {
        $this->app->capsule(Env::class, function () use ($config) {
            Env::load($config['app.envfile']);

            return Env::class;
        });
    }

    /**
     * @inheritdoc
     * @throws
     */
    public function start()
    {
        $this->app->capsule(Env::class);
    }
}
