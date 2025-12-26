<?php

declare(strict_types=1);

namespace Bow\Storage;

use Bow\Configuration\Configuration;
use Bow\Configuration\Loader;

class StorageConfiguration extends Configuration
{
    /**
     * @inheritdoc
     */
    public function create(Loader $config): void
    {
        $this->container->bind('storage', function () use ($config) {
            return Storage::configure($config['storage']);
        });
    }

    /**
     * @inheritdoc
     */
    public function run(): void
    {
        $this->container->make('storage');
    }
}
