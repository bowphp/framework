<?php

namespace Bow\Configuration\Configurations;

use Bow\Http\Cache;
use Bow\Configuration\Loader;
use Bow\Configuration\Configuration;

class CacheConfiguration extends Configuration
{
    /**
     * @inheritdoc
     */
    public function create(Loader $config)
    {
        $this->container->bind('cache', function () use ($config) {
            Cache::confirgure($config['resource.cache']);

            return Cache::class;
        });
    }

    /**
     * @inheritdoc
     */
    public function run()
    {
        $this->container->make('cache');
    }
}
