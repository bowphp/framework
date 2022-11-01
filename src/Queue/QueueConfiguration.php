<?php

declare(strict_types=1);

namespace Bow\Queue;

use Bow\Configuration\Configuration;
use Bow\Configuration\Loader;
use Bow\Queue\Connection as QueueConnection;

class QueueConfiguration extends Configuration
{
    /**
     * @inheritdoc
     */
    public function create(Loader $config)
    {
        $this->container->bind('queue', function () use ($config) {
            return new QueueConnection($config["worker"]);
        });
    }

    /**
     * @inheritdoc
     */
    public function run()
    {
        $this->container->make('queue');
    }
}
