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
     * @param int $tries
     * @param int $sleep
     * @param int $timeout
     * @param int $memory
     * @return void
     */
    public function run(
        string $queue = "default",
        int $tries = 3,
        int $sleep = 5,
        int $timeout = 60,
        int $memory = 128
    ): void {
        $this->connection->setWatch($queue);
        $this->connection->setTries($tries);
        $this->connection->setSleep($sleep);
        $this->connection->work($timeout, $memory);
    }
}
