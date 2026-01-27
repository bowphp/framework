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
     * Redis key for processing jobs
     */
    private const PROCESSING_SUFFIX = ":processing";

    /**
     * Redis key for failed jobs
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
     * Push a job onto the queue
     *
     * @param  QueueTask $job
     * @return bool
     */
    public function push(QueueTask $job): bool
    {
        $payload = $this->buildPayload($job);

        $result = $this->redis->rPush(
            $this->getQueueKey($job->getQueue()),
            json_encode($payload)
        );

        return $result !== false;
    }

    /**
     * Build the job payload
     *
     * @param  QueueTask $job
     * @return array
     */
    private function buildPayload(QueueTask $job): array
    {
        return [
            "id" => $this->generateId(),
            "queue" => $this->getQueue($job->getQueue()),
            "payload" => base64_encode($this->serializeProducer($job)),
            "attempts" => $this->tries,
            "delay" => $job->getDelay(),
            "retry" => $job->getRetry(),
            "available_at" => time() + $job->getDelay(),
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

        // Move job from queue to processing list (atomic operation)
        $rawPayload = $this->redis->brPopLPush(
            $queueKey,
            $processingKey,
            $this->config["block_timeout"] ?? 5
        );

        if ($rawPayload === false) {
            $this->sleep($this->sleep);
            return;
        }

        $this->processJob($rawPayload, $processingKey);
    }

    /**
     * Process a job from the queue
     *
     * @param  string $rawPayload
     * @param  string $processingKey
     * @return void
     */
    private function processJob(string $rawPayload, string $processingKey): void
    {
        $jobData = json_decode($rawPayload, true);
        $producer = null;

        try {
            // Check if job is available for processing
            if (!$this->isJobReady($jobData)) {
                $this->requeue($rawPayload, $processingKey);
                return;
            }

            $producer = $this->unserializeProducer(base64_decode($jobData["payload"]));

            $this->executeTask($producer);
            $this->removeFromProcessing($rawPayload, $processingKey);
            $this->updateProcessingTimeout();
        } catch (Throwable $e) {
            $this->handleJobFailure($rawPayload, $jobData, $producer, $processingKey, $e);
        }
    }

    /**
     * Check if the job is ready to be processed
     *
     * @param  array $jobData
     * @return bool
     */
    private function isJobReady(array $jobData): bool
    {
        return $jobData["available_at"] <= time();
    }

    /**
     * Execute the task
     *
     * @param  QueueTask $producer
     * @return void
     */
    private function executeTask(QueueTask $producer): void
    {
        call_user_func([$producer, "process"]);
    }

    /**
     * Handle job failure
     *
     * @param  string $rawPayload
     * @param  array $jobData
     * @param  QueueTask|null $producer
     * @param  string $processingKey
     * @param  Throwable $exception
     * @return void
     */
    private function handleJobFailure(
        string $rawPayload,
        array $jobData,
        ?QueueTask $producer,
        string $processingKey,
        Throwable $exception
    ): void {
        $this->logError($exception);

        // Store failed job info
        $failedKey = $this->getQueueKey($jobData["queue"]) . self::FAILED_SUFFIX;
        $this->redis->hSet($failedKey, $jobData["id"], $rawPayload);

        if (is_null($producer)) {
            $this->removeFromProcessing($rawPayload, $processingKey);
            $this->sleep(1);
            return;
        }

        $producer->onException($exception);

        if ($this->shouldMarkJobAsFailed($producer, $jobData)) {
            $this->removeFromProcessing($rawPayload, $processingKey);
            $this->sleep(1);
            return;
        }

        // Retry the job
        $this->scheduleJobRetry($jobData, $producer, $processingKey);
        $this->sleep(1);
    }

    /**
     * Determine if the job should be marked as failed
     *
     * @param  QueueTask $producer
     * @param  array $jobData
     * @return bool
     */
    private function shouldMarkJobAsFailed(QueueTask $producer, array $jobData): bool
    {
        return $producer->taskShouldBeDelete() || $jobData["attempts"] <= 0;
    }

    /**
     * Schedule a job for retry
     *
     * @param  array $jobData
     * @param  QueueTask $producer
     * @param  string $processingKey
     * @return void
     */
    private function scheduleJobRetry(array $jobData, QueueTask $producer, string $processingKey): void
    {
        // Update job data for retry
        $jobData["attempts"] = $jobData["attempts"] - 1;
        $jobData["available_at"] = time() + $producer->getDelay();

        $newPayload = json_encode($jobData);

        // Remove from processing and add back to queue
        $this->redis->lRem($processingKey, $newPayload, 0);
        $this->redis->rPush($this->getQueueKey($jobData["queue"]), $newPayload);
    }

    /**
     * Requeue a job that is not yet ready
     *
     * @param  string $rawPayload
     * @param  string $processingKey
     * @return void
     */
    private function requeue(string $rawPayload, string $processingKey): void
    {
        $jobData = json_decode($rawPayload, true);

        $this->redis->lRem($processingKey, $rawPayload, 0);
        $this->redis->rPush($this->getQueueKey($jobData["queue"]), $rawPayload);

        $this->sleep(1);
    }

    /**
     * Remove a job from the processing list
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
     * Log an error
     *
     * @param  Throwable $exception
     * @return void
     */
    private function logError(Throwable $exception): void
    {
        error_log($exception->getMessage());

        try {
            logger()->error($exception->getMessage(), $exception->getTrace());
        } catch (Throwable $loggerException) {
            // Logger not available, already logged to error_log
        }
    }

    /**
     * Flush all jobs from the queue
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
     * Get failed jobs for a queue
     *
     * @param  string|null $queue
     * @return array
     */
    public function getFailedJobs(?string $queue = null): array
    {
        $failedKey = $this->getQueueKey($queue) . self::FAILED_SUFFIX;

        return $this->redis->hGetAll($failedKey);
    }

    /**
     * Retry a failed job
     *
     * @param  string $jobId
     * @param  string|null $queue
     * @return bool
     */
    public function retryFailedJob(string $jobId, ?string $queue = null): bool
    {
        $failedKey = $this->getQueueKey($queue) . self::FAILED_SUFFIX;
        $rawPayload = $this->redis->hGet($failedKey, $jobId);

        if ($rawPayload === false) {
            return false;
        }

        $jobData = json_decode($rawPayload, true);
        $jobData["attempts"] = $this->tries;
        $jobData["available_at"] = time();

        $this->redis->rPush($this->getQueueKey($queue), json_encode($jobData));
        $this->redis->hDel($failedKey, $jobId);

        return true;
    }

    /**
     * Clear all failed jobs for a queue
     *
     * @param  string|null $queue
     * @return void
     */
    public function clearFailedJobs(?string $queue = null): void
    {
        $this->redis->del($this->getQueueKey($queue) . self::FAILED_SUFFIX);
    }
}
