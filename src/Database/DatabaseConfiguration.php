<?php

namespace Bow\Database;

use Bow\Configuration\Configuration;
use Bow\Configuration\Loader;

class DatabaseConfiguration extends Configuration
{
    /**
     * @inheritdoc
     */
    public function create(Loader $config)
    {
        $this->container->bind('db', function () use ($config) {
            return Database::configure(
                is_null($config['db']) ? $config['database'] : $config['db']
            );
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
