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
            $path = $config['app.env_file'];
            if ($path === false) {
                throw new InvalidArgumentException(
                    "The application environment file [.env.json] is not exists. "
                    . "Copy the .env.example.json file to .env.json"
                );
            }

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
