<?php

declare(strict_types=1);

namespace Bow\Configuration;

use Bow\Configuration\Configuration;
use Bow\Configuration\Loader;
use Bow\Support\Env;

class EnvConfiguration extends Configuration
{
    /**
     * @inheritdoc
     */
    public function create(Loader $config): void
    {
        $this->container->bind('env', function () use ($config) {
            Env::load($config['app.env_file']);
        });
    }

    /**
     * @inheritdoc
     */
    public function run(): void
    {
        $this->container->make('env');
    }
}
