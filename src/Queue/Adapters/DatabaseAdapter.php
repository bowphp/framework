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
        $this->table = Database::table($config["table"] ?? "queue_jobs");

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
     * Push a job onto the queue
     *
     * @param  QueueTask $job
     * @return bool
     */
    public function push(QueueTask $job): bool
    {
        $payload = [
            "id" => $this->generateId(),
            "queue" => $this->getQueue(),
            "payload" => base64_encode($this->serializeProducer($job)),
            "attempts" => $this->tries,
            "status" => self::STATUS_WAITING,
            "available_at" => date("Y-m-d H:i:s", time() + $job->getDelay()),
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
        $jobs = $this->fetchPendingJobs($queueName);

        if (count($jobs) === 0) {
            $this->sleep($this->sleep);
            return;
        }

        foreach ($jobs as $job) {
            $this->processJob($job);
        }
    }

    /**
     * Fetch pending jobs from the queue
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
     * Process a single job from the queue
     *
     * @param  stdClass $job
     * @return void
     */
    private function processJob(stdClass $job): void
    {
        $producer = null;

        try {
            $producer = $this->unserializeProducer(base64_decode($job->payload));

            if (!$this->isJobReady($job)) {
                return;
            }

            $this->markJobAs($job->id, self::STATUS_PROCESSING);
            $this->executeTask($producer, $job);
        } catch (Throwable $e) {
            $this->handleJobFailure($job, $producer, $e);
        }
    }

    /**
     * Check if the job is ready to be processed
     *
     * @param  stdClass $job
     * @return bool
     */
    private function isJobReady(stdClass $job): bool
    {
        // Check if the job is available for processing
        if (strtotime($job->available_at) > time()) {
            return false;
        }

        // Skip if the job is still reserved
        if (!is_null($job->reserved_at) && strtotime($job->reserved_at) > time()) {
            return false;
        }

        return true;
    }

    /**
     * Execute the task
     *
     * @param  QueueTask $producer
     * @param  stdClass $job
     * @return void
     * @throws QueryBuilderException
     */
    private function executeTask(QueueTask $producer, stdClass $job): void
    {
        call_user_func([$producer, "process"]);
        $this->markJobAs($job->id, self::STATUS_DONE);
        $this->sleep($this->sleep);
    }

    /**
     * Handle job failure
     *
     * @param  stdClass $job
     * @param  QueueTask|null $producer
     * @param  Throwable $exception
     * @return void
     */
    private function handleJobFailure(stdClass $job, ?QueueTask $producer, Throwable $exception): void
    {
        $this->logError($exception);
        cache("job:failed:" . $job->id, $job->payload);

        if (is_null($producer)) {
            $this->sleep(1);
            return;
        }

        $producer->onException($exception);

        if ($this->shouldMarkJobAsFailed($producer, $job)) {
            $this->markJobAs($job->id, self::STATUS_FAILED);
            $this->sleep(1);
            return;
        }

        $this->scheduleJobRetry($job, $producer);
        $this->sleep(1);
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
            app("logger")->error($exception->getMessage(), $exception->getTrace());
        } catch (Throwable $loggerException) {
            // Logger not available, already logged to error_log
        }
    }

    /**
     * Determine if the job should be marked as failed
     *
     * @param  QueueTask $producer
     * @param  stdClass $job
     * @return bool
     */
    private function shouldMarkJobAsFailed(QueueTask $producer, stdClass $job): bool
    {
        return $producer->taskShouldBeDelete() || $job->attempts <= 0;
    }

    /**
     * Schedule a job for retry
     *
     * @param  stdClass $job
     * @param  QueueTask $producer
     * @return void
     * @throws QueryBuilderException
     */
    private function scheduleJobRetry(stdClass $job, QueueTask $producer): void
    {
        $this->table->where("id", $job->id)->update([
            "status" => self::STATUS_RESERVED,
            "attempts" => $job->attempts - 1,
            "available_at" => date("Y-m-d H:i:s", time() + $producer->getDelay()),
            "reserved_at" => date("Y-m-d H:i:s", time() + $producer->getRetry()),
        ]);
    }

    /**
     * Update job status
     *
     * @param  string $jobId
     * @param  string $status
     * @return void
     * @throws QueryBuilderException
     */
    private function markJobAs(string $jobId, string $status): void
    {
        $this->table->where("id", $jobId)->update(["status" => $status]);
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
