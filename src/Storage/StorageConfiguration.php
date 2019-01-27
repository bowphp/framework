<?php

namespace Bow\Storage;

use Bow\Configuration\Configuration;
use Bow\Configuration\Loader;

class StorageConfiguration extends Configuration
{
    /**
     * @inheritdoc
     */
    public function create(Loader $config)
    {
        $this->container->bind('storage', function () use ($config) {
            return Storage::configure($config['storage']);
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
