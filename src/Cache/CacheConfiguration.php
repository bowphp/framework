<?php

declare(strict_types=1);

namespace Bow\Cache;

use Bow\Configuration\Configuration;
use Bow\Configuration\Loader;
use Bow\Cache\Cache;

class CacheConfiguration extends Configuration
{
    /**
     * @inheritdoc
     */
    public function create(Loader $config): void
    {
        $this->container->bind('cache', function () use ($config) {
            Cache::confirgure($config['storage.cache']);

            return Cache::class;
        });
    }

    /**
     * @inheritdoc
     */
    public function run(): void
    {
        $this->container->make('cache');
    }
}
