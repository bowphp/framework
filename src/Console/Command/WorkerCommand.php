<?php

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
        $retry = (int) $this->arg->getParameter('--retry', 3);
        $default = $this->arg->getParameter('--queue', "default");

        $queue = app("queue");

        if (!is_null($connection)) {
            $queue->setConnection($connection);
        }

        $worker = new WorkerService();
        $worker->setConnection($queue->getAdapter());
        $worker->run($default, $retry);
    }
}
