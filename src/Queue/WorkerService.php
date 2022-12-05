<?php

declare(strict_types=1);

namespace Bow\Queue;

use Bow\Queue\Adapters\QueueAdapter;

class WorkerService
{
    /**
     * Determine the instance of QueueAdapter
     *
     * @var QueueAdapter
     */
    private QueueAdapter $connection;

    /**
     * Make connection base on default name
     *
     * @param string $name
     * @return QueueAdapter
     */
    public function setConnection(QueueAdapter $connection): void
    {
        $this->connection = $connection;
    }

    /**
     * Start the consumer
     *
     * @param string $queue
     * @param integer $retry
     * @return void
     */
    public function run(string $queue = "default", int $retry = 60): void
    {
        $this->connection->setWatch($queue);
        $this->connection->setRetry($retry);

        while (true) {
            $this->connection->run();
        }
    }
}
