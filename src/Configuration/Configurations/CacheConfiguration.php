<?php

namespace Bow\Configuration;

use Bow\Http\Cache;
use Bow\Configuration\Loader;
use Bow\Configuration\Configuration;

class CacheConfiguration extends Configuration
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
        $this->container->bind('cache', function () use ($config) {
            Cache::confirgure($config['resource.cache']);

            return Cache::class;
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
        $this->container->make('cache');
    }
}
