<?php

declare(strict_types=1);

namespace Bow\Console\Command;

use Bow\Queue\WorkerService;

class WorkerCommand extends AbstractCommand
{
    /**
     * The run server command
     *
     * @param string $connection
     * @return void
     */
    public function run(?string $connection = null): void
    {
        $tries = (int) $this->arg->getParameter('--tries', 3);
        $default = $this->arg->getParameter('--queue', "default");
        $memory = (int) $this->arg->getParameter('--memory', 126);
        $timout = (int) $this->arg->getParameter('--timout', 60);
        $sleep = (int) $this->arg->getParameter('--sleep', 60);

        $queue = app("queue");

        if (!is_null($connection)) {
            $queue->setConnection($connection);
        }

        $worker = $this->getWorderService();
        $worker->setConnection($queue->getAdapter());
        $worker->run($default, $tries, $sleep, $timout, $memory);
    }

    /**
     * Flush the queue
     *
     * @param ?string $connection
     * @return void
     */
    public function flush(?string $connection = null)
    {
        $connection_queue = $this->arg->getParameter('--queue');

        $queue = app("queue");

        if (!is_null($connection)) {
            $queue->setConnection($connection);
        }

        $adapter = $queue->getAdapter();
        $adapter->flush($connection_queue);
    }

    /**
     * Get the worker service
     *
     * @return WorkerService
     */
    private function getWorderService()
    {
        return new WorkerService();
    }
}
