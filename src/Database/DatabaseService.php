<?php

namespace Bow\Database;

use Bow\Config\Config;
use Bow\Application\Service as BowService;

class DatabaseService extends BowService
{
    /**
     * Configuration du service
     *
     * @param Config $config
     * @return void
     * @throws
     */
    public function make(Config $config)
    {
        $this->app->capsule(Database::class, function () use ($config) {
            return Database::configure($config['db']);
        });
    }

    /**
     * Démarrage du service
     *
     * @return void
     */
    public function start()
    {
        $this->app->capsule(Database::class);
    }
}
