<?php

namespace Bow\Resource;

use Bow\Config\Config;
use Bow\Application\Service as BowService;

class StorageService extends BowService
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
        $this->app->capsule(Storage::class, function () use ($config) {
            return Storage::configure($config['resource']);
        });
    }

    /**
     * Démarrage du service
     *
     * @return void
     * @throws
     */
    public function start()
    {
        $this->app->capsule(Storage::class);
    }
}
