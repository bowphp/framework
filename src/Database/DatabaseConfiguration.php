<?php

namespace Bow\Database;

use Bow\Configuration\Loader;
use Bow\Configuration\Configuration;

class DatabaseConfiguration extends Configuration
{
    /**
     * Configuration du service
     *
     * @param Loader $config
     * @return void
     * @throws
     */
    public function create(Loader $config)
    {
        $this->container->bind('db', function () use ($config) {
            return Database::configure($config['db']);
        });
    }

    /**
     * Démarrage du service
     *
     * @return void
     */
    public function run()
    {
        $this->container->make('db');
    }
}
