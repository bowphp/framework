<?php

declare(strict_types=1);

namespace Bow\Queue\Adapters;

use Bow\Database\Database;
use Bow\Database\Exception\QueryBuilderException;
use Bow\Database\QueryBuilder;
use Bow\Queue\QueueTask;
use ErrorException;
use stdClass;
use Throwable;

class DatabaseAdapter extends QueueAdapter
{
    /**
     * Job status constants
     */
    private const STATUS_WAITING = "waiting";
    private const STATUS_RESERVED = "reserved";
    private const STATUS_PROCESSING = "processing";
    private const STATUS_DONE = "done";
    private const STATUS_FAILED = "failed";

    /**
     * The query builder instance for the queue table
     *
     * @var QueryBuilder
     */
    private QueryBuilder $table;

    /**
     * Configure the database queue adapter
     *
     * @param  array $config
     * @return DatabaseAdapter
     */
    public function configure(array $config): DatabaseAdapter
    {
        $this->table = Database::table($config["table"] ?? "queue_tasks");

        return $this;
    }

    /**
     * Get the size of the queue.
     *
     * @param  string|null $queue
     * @return int
     * @throws QueryBuilderException
     */
    public function size(?string $queue = null): int
    {
        return $this->table
            ->where("queue", $this->getQueue($queue))
            ->count();
    }

    /**
     * Push a task onto the queue
     *
     * @param  QueueTask $task
     * @return bool
     */
    public function push(QueueTask $task): bool
    {
        $task->setId($this->generateId());

        $payload = [
            "id" => $task->getId(),
            "queue" => $this->getQueue(),
            "payload" => base64_encode($this->serializeProducer($task)),
            "attempts" => $this->tries,
            "status" => self::STATUS_WAITING,
            "available_at" => date("Y-m-d H:i:s", time() + (method_exists($task, 'getDelay') ? $task->getDelay() : 0)),
            "reserved_at" => null,
            "created_at" => date("Y-m-d H:i:s"),
        ];

        return $this->table->insert($payload) > 0;
    }

    /**
     * Run the queue worker
     *
     * @param  string|null $queue
     * @return void
     * @throws QueryBuilderException
     * @throws ErrorException
     */
    public function run(?string $queue = null): void
    {
        $queueName = $this->getQueue($queue);
        $tasks = $this->fetchPendingJobs($queueName);

        if (count($tasks) === 0) {
            $this->sleep($this->sleep);
            return;
        }

        foreach ($tasks as $task) {
            $this->processJob($task);
        }
    }

    /**
     * Fetch pending tasks from the queue
     *
     * @param  string $queueName
     * @return array
     * @throws QueryBuilderException
     */
    private function fetchPendingJobs(string $queueName): array
    {
        return $this->table
            ->where("queue", $queueName)
            ->whereIn("status", [self::STATUS_WAITING, self::STATUS_RESERVED])
            ->get();
    }

    /**
     * Process a single task from the queue
     *
     * @param  stdClass $task
     * @return void
     */
    private function processJob(stdClass $task): void
    {
        $producer = null;

        try {
            $producer = $this->unserializeProducer(base64_decode($task->payload));

            if (!$this->isJobReady($task)) {
                return;
            }

            $this->markJobAs($task->id, self::STATUS_PROCESSING);
            $this->executeTask($producer, $task);
        } catch (Throwable $e) {
            $this->handleJobFailure($task, $producer, $e);
        }
    }

    /**
     * Check if the task is ready to be processed
     *
     * @param  stdClass $task
     * @return bool
     */
    private function isJobReady(stdClass $task): bool
    {
        // Check if the task is available for processing
        if (strtotime($task->available_at) > time()) {
            return false;
        }

        // Skip if the task is still reserved
        if (!is_null($task->reserved_at) && strtotime($task->reserved_at) > time()) {
            return false;
        }

        return true;
    }

    /**
     * Execute the task
     *
     * @param  QueueTask $task
     * @param  stdClass $item
     * @return void
     * @throws QueryBuilderException
     */
    private function executeTask(QueueTask $task, stdClass $item): void
    {
        $this->logProcesingTask($task);
        if (!method_exists($task, 'process')) {
            throw new \RuntimeException('Job does not have a process or handle method.');
        }
        $task->process();
        $this->logProcessedTask($task);
        $this->markJobAs($item->id, self::STATUS_DONE);
        $this->sleep($this->sleep);
    }

    /**
     * Handle task failure
     *
     * @param  stdClass $task
     * @param  QueueTask|null $producer
     * @param  Throwable $exception
     * @return void
     */
    private function handleJobFailure(stdClass $task, ?QueueTask $producer, Throwable $exception): void
    {
        $this->logError($exception);

        cache("task:failed:" . $task->id, $task->payload);
        error_log('Job failed: ' . (is_object($producer) ? get_class($producer) : 'unknown') . ' with ID: ' . (is_object($producer) && method_exists($producer, 'getId') ? $producer->getId() : 'unknown'));

        if (is_null($producer)) {
            $this->sleep(1);
            return;
        }

        if (method_exists($producer, 'onException')) {
            $producer->onException($exception);
        }

        if ($this->shouldMarkJobAsFailed($producer, $task)) {
            $this->markJobAs($task->id, self::STATUS_FAILED);
            $this->sleep(1);
            return;
        }

        $this->scheduleJobRetry($task, $producer);
        $this->sleep(1);
    }

    /**
     * Determine if the task should be marked as failed
     *
     * @param  QueueTask $producer
     * @param  stdClass $task
     * @return bool
     */
    private function shouldMarkJobAsFailed(QueueTask $producer, stdClass $task): bool
    {
        return $producer->taskShouldBeDelete() || $task->attempts <= 0;
    }

    /**
     * Schedule a task for retry
     *
     * @param  stdClass $task
     * @param  QueueTask $producer
     * @return void
     * @throws QueryBuilderException
     */
    private function scheduleJobRetry(stdClass $task, QueueTask $producer): void
    {
        $this->table->where("id", $task->id)->update([
            "status" => self::STATUS_RESERVED,
            "attempts" => $task->attempts - 1,
            "available_at" => date("Y-m-d H:i:s", time() + $producer->getDelay()),
            "reserved_at" => date("Y-m-d H:i:s", time() + $producer->getRetry()),
        ]);
    }

    /**
     * Update task status
     *
     * @param  string $taskId
     * @param  string $status
     * @return void
     * @throws QueryBuilderException
     */
    private function markJobAs(string $taskId, string $status): void
    {
        $this->table->where("id", $taskId)->update(["status" => $status]);
    }

    /**
     * Flush the queue table
     *
     * @param  string|null $queue
     * @return void
     * @throws QueryBuilderException
     */
    public function flush(?string $queue = null): void
    {
        if (is_null($queue)) {
            $this->table->truncate();
            return;
        }

        $this->table->where("queue", $queue)->delete();
    }
}
