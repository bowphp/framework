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
    public function create(Loader $config): void
    {
        $this->container->bind('queue', function () use ($config) {
            return new QueueConnection($config["worker"] ?? $config["queue"]);
        });
    }

    /**
     * @inheritdoc
     */
    public function run(): void
    {
        $this->container->make('queue');
    }
}
