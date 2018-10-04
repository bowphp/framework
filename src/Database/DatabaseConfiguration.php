<?php

namespace Bow\Database;

use Bow\Configuration\Loader;
use Bow\Configuration\Configuration;

class DatabaseConfiguration extends Configuration
{
    /**
     * @inheritdoc
     */
    public function create(Loader $config)
    {
        $this->container->bind('db', function () use ($config) {
            return Database::configure($config['db']);
        });
    }

    /**
     * @inheritdoc
     */
    public function run()
    {
        $this->container->make('db');
    }
}
