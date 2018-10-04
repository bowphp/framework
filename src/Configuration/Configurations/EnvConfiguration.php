<?php

namespace Bow\Configuration\Configurations;

use Bow\Configuration\Configuration;
use Bow\Configuration\Loader;
use Bow\Support\Env;

class EnvConfiguration extends Configuration
{
    /**
     * @inheritdoc
     */
    public function create(Loader $config)
    {
        $this->container->bind('env', function () use ($config) {
            Env::load($config['app.envfile']);
        });
    }

    /**
     * @inheritdoc
     */
    public function run()
    {
        $this->container->make('env');
    }
}
