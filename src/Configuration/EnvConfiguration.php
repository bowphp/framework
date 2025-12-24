<?php

declare(strict_types=1);

namespace Bow\Configuration;

use Bow\Support\Env;
use InvalidArgumentException;

class EnvConfiguration extends Configuration
{
    /**
     * @inheritdoc
     */
    public function create(Loader $config): void
    {
        $this->container->bind('env', function () use ($config) {
            Env::configure($config['app.env_file']);

            return Env::getInstance();
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
