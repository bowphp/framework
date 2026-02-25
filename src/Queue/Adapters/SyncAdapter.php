<?php

declare(strict_types=1);

namespace Bow\Queue\Adapters;

use Bow\Queue\QueueTask;

class SyncAdapter extends QueueAdapter
{
    /**
     * Adapter configuration
     *
     * @var array
     */
    private array $config = [];

    /**
     * Configure SyncAdapter driver
     *
     * @param  array $config
     * @return $this
     */
    public function configure(array $config): self
    {
        $this->config = $config;
        return $this;
    }

    /**
     * Queue a job and execute it immediately (synchronously)
     *
     * @param  QueueTask $job
     * @return bool
     */
    public function push(QueueTask $job): bool
    {
        try {
            if (!method_exists($job, 'process')) {
                throw new \RuntimeException('Job does not have a process or handle method.');
            }
            error_log('Processing job: ' . get_class($job) . ' with ID: ' . (method_exists($job, 'getId') ? $job->getId() : 'unknown'));
            $job->process();
        } catch (\Throwable $e) {
            // Optionally log or handle error
            error_log('Job failed: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            throw $e;
        }

        if (method_exists($job, 'getDelay')) {
            $this->sleep($job->getDelay());
        }

        return true;
    }
}
