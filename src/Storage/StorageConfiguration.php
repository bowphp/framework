<?php

namespace Bow\Storage;

use Bow\Configuration\Loader;
use Bow\Configuration\Configuration;

class StorageConfiguration extends Configuration
{
    /**
     * @inheritdoc
     */
    public function create(Loader $config)
    {
        $this->container->bind('storage', function () use ($config) {
            return Storage::configure($config['resource']);
        });
    }

    /**
     * @inheritdoc
     */
    public function run()
    {
        $this->container->make('storage');
    }
}
