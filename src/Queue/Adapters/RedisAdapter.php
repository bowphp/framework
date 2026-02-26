<?php

declare(strict_types=1);

namespace Bow\Queue\Adapters;

use Bow\Database\Redis as RedisStore;
use Bow\Queue\QueueTask;
use Redis;
use RuntimeException;
use Throwable;

class RedisAdapter extends QueueAdapter
{
    /**
     * Redis key prefix for queues
     */
    private const QUEUE_PREFIX = "queues:";

    /**
     * Redis key for processing tasks
     */
    private const PROCESSING_SUFFIX = ":processing";

    /**
     * Redis key for failed tasks
     */
    private const FAILED_SUFFIX = ":failed";

    /**
     * The Redis client instance
     *
     * @var Redis
     */
    private Redis $redis;

    /**
     * The adapter configuration
     *
     * @var array
     */
    private array $config = [];

    /**
     * Configure the Redis queue adapter
     *
     * @param  array $config
     * @return RedisAdapter
     */
    public function configure(array $config): RedisAdapter
    {
        if (!extension_loaded("redis")) {
            throw new RuntimeException(
                "The Redis PHP extension is required. Please install it."
            );
        }

        $this->config = $config;
        $this->redis = RedisStore::getClient();

        if (isset($config["database"])) {
            $this->redis->select($config["database"]);
        }

        if (isset($config["queue"])) {
            $this->setQueue($config["queue"]);
        }

        return $this;
    }

    /**
     * Get the size of the queue
     *
     * @param  string|null $queue
     * @return int
     */
    public function size(?string $queue = null): int
    {
        return (int) $this->redis->lLen($this->getQueueKey($queue));
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

        $payload = $this->buildPayload($task);

        $result = $this->redis->rPush(
            $this->getQueueKey($task->getQueue()),
            json_encode($payload)
        );

        return $result !== false;
    }

    /**
     * Build the task payload
     *
     * @param  QueueTask $task
     * @return array
     */
    private function buildPayload(QueueTask $task): array
    {
        return [
            "id" => $this->generateId(),
            "queue" => $this->getQueue($task->getQueue()),
            "payload" => base64_encode($this->serializeProducer($task)),
            "attempts" => $this->tries,
            "delay" => $task->getDelay(),
            "retry" => $task->getRetry(),
            "available_at" => time() + $task->getDelay(),
            "created_at" => time(),
        ];
    }

    /**
     * Run the queue worker
     *
     * @param  string|null $queue
     * @return void
     */
    public function run(?string $queue = null): void
    {
        $queueKey = $this->getQueueKey($queue);
        $processingKey = $queueKey . self::PROCESSING_SUFFIX;

        // Move task from queue to processing list (atomic operation)
        $rawPayload = $this->redis->brPopLPush(
            $queueKey,
            $processingKey,
            $this->config["block_timeout"] ?? 5
        );

        if ($rawPayload === false) {
            $this->sleep($this->sleep);
            return;
        }

        $this->processTask($rawPayload, $processingKey);
    }

    /**
     * Process a task from the queue
     *
     * @param  string $rawPayload
     * @param  string $processingKey
     * @return void
     */
    private function processTask(string $rawPayload, string $processingKey): void
    {
        $taskData = json_decode($rawPayload, true);
        $task = null;

        try {
            // Check if task is available for processing
            if (!$this->isTaskReady($taskData)) {
                $this->requeue($rawPayload, $processingKey);
                return;
            }

            $task = $this->unserializeProducer(base64_decode($taskData["payload"]));

            $this->executeTask($task);
            $this->removeFromProcessing($rawPayload, $processingKey);
            $this->updateProcessingTimeout();
        } catch (Throwable $e) {
            $this->handleTaskFailure($rawPayload, $taskData, $task, $processingKey, $e);
        }
    }

    /**
     * Check if the task is ready to be processed
     *
     * @param  array $taskData
     * @return bool
     */
    private function isTaskReady(array $taskData): bool
    {
        return $taskData["available_at"] <= time();
    }

    /**
     * Execute the task
     *
     * @param  QueueTask $task
     * @return void
     */
    private function executeTask(QueueTask $task): void
    {
        $this->logProcesingTask($task);

        $task->process();

        $this->logProcessedTask($task);
    }

    /**
     * Handle task failure
     *
     * @param  string $rawPayload
     * @param  array $taskData
     * @param  QueueTask|null $task
     * @param  string $processingKey
     * @param  Throwable $exception
     * @return void
     */
    private function handleTaskFailure(
        string $rawPayload,
        array $taskData,
        ?QueueTask $task,
        string $processingKey,
        Throwable $exception
    ): void {
        $this->logError($exception);

        // Store failed task info
        $failedKey = $this->getQueueKey($taskData["queue"]) . self::FAILED_SUFFIX;
        $this->redis->hSet($failedKey, $taskData["id"], $rawPayload);

        if (is_null($task)) {
            $this->removeFromProcessing($rawPayload, $processingKey);
            $this->sleep(1);
            return;
        }

        $task->onException($exception);
        $this->logFailedTask($task, $exception);

        if ($this->shouldMarkTaskAsFailed($task, $taskData)) {
            $this->removeFromProcessing($rawPayload, $processingKey);
            $this->sleep(1);
            return;
        }

        // Retry the task
        $this->scheduleTaskRetry($taskData, $task, $processingKey);
        $this->sleep(1);
    }

    /**
     * Determine if the task should be marked as failed
     *
     * @param  QueueTask $producer
     * @param  array $taskData
     * @return bool
     */
    private function shouldMarkTaskAsFailed(QueueTask $producer, array $taskData): bool
    {
        return $producer->taskShouldBeDelete() || $taskData["attempts"] <= 0;
    }

    /**
     * Schedule a task for retry
     *
     * @param  array $taskData
     * @param  QueueTask $producer
     * @param  string $processingKey
     * @return void
     */
    private function scheduleTaskRetry(array $taskData, QueueTask $producer, string $processingKey): void
    {
        // Update task data for retry
        $taskData["attempts"] = $taskData["attempts"] - 1;
        $taskData["available_at"] = time() + $producer->getDelay();

        $newPayload = json_encode($taskData);

        // Remove from processing and add back to queue
        $this->redis->lRem($processingKey, $newPayload, 0);
        $this->redis->rPush($this->getQueueKey($taskData["queue"]), $newPayload);
    }

    /**
     * Requeue a task that is not yet ready
     *
     * @param  string $rawPayload
     * @param  string $processingKey
     * @return void
     */
    private function requeue(string $rawPayload, string $processingKey): void
    {
        $taskData = json_decode($rawPayload, true);

        $this->redis->lRem($processingKey, $rawPayload, 0);
        $this->redis->rPush($this->getQueueKey($taskData["queue"]), $rawPayload);

        $this->sleep(1);
    }

    /**
     * Remove a task from the processing list
     *
     * @param  string $rawPayload
     * @param  string $processingKey
     * @return void
     */
    private function removeFromProcessing(string $rawPayload, string $processingKey): void
    {
        $this->redis->lRem($processingKey, $rawPayload, 0);
    }

    /**
     * Get the Redis key for a queue
     *
     * @param  string|null $queue
     * @return string
     */
    private function getQueueKey(?string $queue = null): string
    {
        return self::QUEUE_PREFIX . $this->getQueue($queue);
    }

    /**
     * Flush all tasks from the queue
     *
     * @param  string|null $queue
     * @return void
     */
    public function flush(?string $queue = null): void
    {
        $queueKey = $this->getQueueKey($queue);

        $this->redis->del($queueKey);
        $this->redis->del($queueKey . self::PROCESSING_SUFFIX);
        $this->redis->del($queueKey . self::FAILED_SUFFIX);
    }

    /**
     * Get failed tasks for a queue
     *
     * @param  string|null $queue
     * @return array
     */
    public function getFailedTasks(?string $queue = null): array
    {
        $failedKey = $this->getQueueKey($queue) . self::FAILED_SUFFIX;

        return $this->redis->hGetAll($failedKey);
    }

    /**
     * Retry a failed task
     *
     * @param  string $taskId
     * @param  string|null $queue
     * @return bool
     */
    public function retryFailedTask(string $taskId, ?string $queue = null): bool
    {
        $failedKey = $this->getQueueKey($queue) . self::FAILED_SUFFIX;
        $rawPayload = $this->redis->hGet($failedKey, $taskId);

        if ($rawPayload === false) {
            return false;
        }

        $taskData = json_decode($rawPayload, true);
        $taskData["attempts"] = $this->tries;
        $taskData["available_at"] = time();

        $this->redis->rPush($this->getQueueKey($queue), json_encode($taskData));
        $this->redis->hDel($failedKey, $taskId);

        return true;
    }

    /**
     * Clear all failed tasks for a queue
     *
     * @param  string|null $queue
     * @return void
     */
    public function clearFailedTasks(?string $queue = null): void
    {
        $this->redis->del($this->getQueueKey($queue) . self::FAILED_SUFFIX);
    }
}
