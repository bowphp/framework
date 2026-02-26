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
     * Queue a task and execute it immediately (synchronously)
     *
     * @param  QueueTask $task
     * @return bool
     */
    public function push(QueueTask $task): bool
    {
        $task->setId($this->generateId());

        try {
            if (!method_exists($task, 'process')) {
                throw new \RuntimeException('Task does not have a process or handle method.');
            }
            $this->logProcesingTask($task);

            $task->process();

            $this->logProcessedTask($task);
        } catch (\Throwable $e) {
            // Optionally log or handle error
            $this->logFailedTask($task, $e);
            throw $e;
        }

        if (method_exists($task, 'getDelay')) {
            $this->sleep($task->getDelay());
        }

        return true;
    }
}
