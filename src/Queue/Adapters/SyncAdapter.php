<?php

declare(strict_types=1);

namespace Bow\Queue\Adapters;

use Bow\Queue\ProducerService;
use Bow\Queue\Adapters\QueueAdapter;

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
     * @param array $config
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
     * @param ProducerService $producer
     * @return void
     */
    public function push(ProducerService $producer): void
    {
        $producer->process();
    }
}
