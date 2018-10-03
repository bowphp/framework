<?php

namespace Bow\Configuration\Configurations;

use Bow\Support\Env;
use Bow\Configuration\Loader;
use Bow\Configuration\Configuration;

class EnvConfiguration extends Configuration
{
    /**
     * @inheritdoc
     * @throws
     */
    public function create(Loader $config)
    {
        $this->container->bind('env', function () use ($config) {
            Env::load($config['app.envfile']);
        });
    }

    /**
     * @inheritdoc
     * @throws
     */
    public function run()
    {
        $this->container->make('env');
    }
}
