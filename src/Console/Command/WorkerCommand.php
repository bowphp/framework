<?php

namespace Bow\Console\Command;

use Bow\Queue\Adapters\Connection as QueueConnection;
use Bow\Queue\WorkerService;


class WorkerCommand extends AbstractCommand
{
    /**
     * The run server command
     * 
     * @param string $connection
     * @return void
     */
    public function run(string $connection = null)
    {
        $retry = (int) $this->arg->options('--retry', 3);
        $default = $this->arg->options('--queue', "default");

        $queue = app("queue");

        if (!is_null($connection)) {
            $queue->setConnection($connection);
        }

        $worker = new WorkerService();
        $worker->setConnection($queue->getAdapter());
        $worker->run($default, $retry);
    }
}