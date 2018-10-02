<?php

namespace Bow\Resource;

use Bow\Configuration\Loader;
use Bow\Configuration\Configuration;

class StorageConfiguration extends Configuration
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
        $this->container->bind('storage', function () use ($config) {
            return Storage::configure($config['resource']);
        });
    }

    /**
     * Démarrage du service
     *
     * @return void
     * @throws
     */
    public function run()
    {
        $this->container->make('storage');
    }
}
