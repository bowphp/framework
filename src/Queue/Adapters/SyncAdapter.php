<?php

declare(strict_types=1);

namespace Bow\Queue\Adapters;

use Bow\Queue\QueueTask;

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
     * @param  QueueTask $job
     * @return bool
     */
    public function push(QueueTask $job): bool
    {
        $job->process();

        $this->sleep($job->getDelay());

        return true;
    }
}
