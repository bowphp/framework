<?php

declare(strict_types=1);

namespace Bow\Queue\Adapters;

use Bow\Queue\QueueJob;

class SyncAdapter extends QueueAdapter
{
    /**
     * Define the config
     *
     * @var array
     */
    private array $config;

    /**
     * Configure SyncAdapter driver
     *
     * @param  array $config
     * @return mixed
     */
    public function configure(array $config): SyncAdapter
    {
        $this->config = $config;

        return $this;
    }

    /**
     * Queue a job
     *
     * @param  QueueJob $producer
     * @return void
     */
    public function push(QueueJob $producer): void
    {
        $producer->process();
    }
}
