<?php

declare(strict_types=1);

namespace Bow\Queue\Adapters;

use Bow\Queue\QueueTask;
use Pheanstalk\Contract\JobIdInterface;
use Pheanstalk\Contract\PheanstalkPublisherInterface;
use Pheanstalk\Exception\ConnectionException;
use Pheanstalk\Pheanstalk;
use Pheanstalk\Values\Job;
use Pheanstalk\Values\Timeout;
use Pheanstalk\Values\TubeName;
use RuntimeException;
use Throwable;

class BeanstalkdAdapter extends QueueAdapter
{
    /**
     * Maximum priority value for Beanstalkd
     */
    private const MAX_PRIORITY = 4294967295;

    /**
     * Cache key for storing queue names
     */
    private const QUEUE_CACHE_KEY = "beanstalkd:queues";

    /**
     * Seconds the server is allowed to hold a reserve before answering
     *
     * Pheanstalk 8 adds this to the socket receive timeout while it waits, so
     * any value is safe there. Pheanstalk 5 does not, and reads with the plain
     * receive timeout, which defaults to 10 seconds: past that the socket read
     * expires before beanstalkd answers and an idle tube surfaces as a
     * connection error instead of "no job". Stay below it to support both.
     */
    private const RESERVE_TIMEOUT = 5;

    /**
     * The Pheanstalk client instance
     *
     * @var Pheanstalk
     */
    private Pheanstalk $pheanstalk;

    /**
     * Configure the Beanstalkd queue adapter
     *
     * @param  array $config
     * @return BeanstalkdAdapter
     */
    public function configure(array $config): BeanstalkdAdapter
    {
        if (!class_exists(Pheanstalk::class)) {
            throw new RuntimeException("Please install the pda/pheanstalk package");
        }

        $timeout = isset($config["timeout"]) && $config["timeout"]
            ? new Timeout($config["timeout"])
            : null;

        $this->pheanstalk = Pheanstalk::create(
            $config["hostname"],
            $config["port"],
            $timeout,
        );

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
        $tubeName = new TubeName($this->getQueue($queue));

        return (int) $this->pheanstalk->statsTube($tubeName)->currentJobsReady;
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

        $this->registerQueueName($task->getQueue());

        $this->pheanstalk->useTube(new TubeName($task->getQueue()));

        $this->pheanstalk->put(
            $this->serializeProducer($task),
            $this->getPriority($task->getPriority()),
            $task->getDelay(),
            $task->getRetry()
        );

        return true;
    }

    /**
     * Register a queue name in cache for later reference
     *
     * @param  string $queueName
     * @return void
     */
    private function registerQueueName(string $queueName): void
    {
        $queues = (array) cache(self::QUEUE_CACHE_KEY);

        if (!in_array($queueName, $queues)) {
            $queues[] = $queueName;
            cache(self::QUEUE_CACHE_KEY, $queues);
        }
    }

    /**
     * Convert priority level to Beanstalkd priority value
     *
     * Priority mapping:
     * - 0: Highest priority (urgent)
     * - 1: Default priority (normal)
     * - 2: Default priority (normal)
     * - 3+: Lowest priority (bulk/background)
     *
     * @param  int $priority
     * @return int
     */
    public function getPriority(int $priority): int
    {
        return match (true) {
            $priority <= 0 => 0,
            $priority > 2 => self::MAX_PRIORITY,
            default => PheanstalkPublisherInterface::DEFAULT_PRIORITY,
        };
    }

    /**
     * Run the queue worker
     *
     * @param  string|null $queue
     * @return void
     */
    public function run(?string $queue = null): void
    {
        $job = $this->reserveNextJob($this->getQueue($queue));

        // The tube stayed empty for the whole reserve window, or the server is
        // unreachable. Neither is a task failure, so there is nothing to report.
        if (is_null($job)) {
            return;
        }

        $task = null;

        try {
            $task = $this->unserializeProducer($job->getData());

            $this->executeTask($task);
            $this->pheanstalk->touch($job);
            $this->pheanstalk->delete($job);
            $this->updateProcessingTimeout();
        } catch (Throwable $e) {
            $this->handleTaskFailure($job, $task, $e);
        }
    }

    /**
     * Reserve the next job to process on the given tube
     *
     * An idle tube is not a failure and must not be reported as one. A plain
     * reserve() blocks server side until a job shows up, so the socket read
     * expires first and an empty queue surfaces as "Socket error 35: Resource
     * temporarily unavailable" logged as a failed task on every idle window.
     * reserveWithTimeout() lets beanstalkd answer TIMED_OUT instead, which
     * Pheanstalk reports as null.
     *
     * @param  string $queueName
     * @return Job|null
     */
    private function reserveNextJob(string $queueName): ?Job
    {
        try {
            $this->pheanstalk->watch(new TubeName($queueName));

            return $this->pheanstalk->reserveWithTimeout(self::RESERVE_TIMEOUT);
        } catch (ConnectionException $exception) {
            // The server is unreachable, which says nothing about any task.
            // Pheanstalk drops the socket on this and reconnects on the next
            // command, so back off rather than spin on reconnect attempts.
            $this->logError($exception);
            $this->sleep(1);

            return null;
        }
    }

    /**
     * Execute the task
     *
     * @param  QueueTask $task
     * @return void
     */
    private function executeTask(QueueTask $task): void
    {
        $this->logProcessingTask($task);

        $task->process();

        $this->logProcessedTask($task);
    }

    /**
     * Handle task failure
     *
     * @param  Job|null $job
     * @param  QueueTask|null $task
     * @param  Throwable $exception
     * @return void
     */
    private function handleTaskFailure(?Job $job, ?QueueTask $task, Throwable $exception): void
    {
        $this->logError($exception);

        $this->logFailedTask($task, $exception);

        if (is_null($job)) {
            return;
        }

        // Poison message: the reserved body could not be unserialized into a
        // QueueTask ($task is null), so there is no task id to key on and no
        // task to retry. Keep the raw body for inspection, then delete the job
        // BEFORE dereferencing $task below — leaving it would redeliver on TTR
        // and crash-loop the worker.
        if (is_null($task)) {
            $this->recordFailedPayload("task:failed:job:" . $job->getId(), $job->getData());
            $this->pheanstalk->delete($job);
            return;
        }

        if ($this->resolveFailedTask($job, $task, $exception)) {
            $this->pheanstalk->delete($job);
        } else {
            $this->releaseTask($job, $task);
        }

        $this->sleep(1);
    }

    /**
     * Run the task defined failure handling and decide whether to drop the job
     *
     * Everything the task exposes here is overridable, so it is user code:
     * getId(), getData(), onException() and taskShouldBeDelete() may all throw.
     * A throw must not escape, otherwise the job is never deleted nor released,
     * it redelivers on TTR and the worker crash-loops on it. getPriority() and
     * getDelay(), used when releasing, are final and cannot throw.
     *
     * @param  Job $job
     * @param  QueueTask $task
     * @param  Throwable $exception
     * @return bool Whether the job should be deleted
     */
    private function resolveFailedTask(Job $job, QueueTask $task, Throwable $exception): bool
    {
        try {
            $this->recordFailedPayload(
                "task:failed:" . $task->getId(),
                method_exists($task, 'getData') ? $task->getData() : ""
            );

            $task->onException($exception);

            return $task->taskShouldBeDelete();
        } catch (Throwable $taskException) {
            $this->logError($taskException);

            // The task cannot handle its own failure, so retrying it would most
            // likely break the same way. Keep the raw body under the job id,
            // since the task id may be exactly what failed, then drop the job.
            $this->recordFailedPayload("task:failed:job:" . $job->getId(), $job->getData());

            return true;
        }
    }

    /**
     * Release the task back to the queue for retry
     *
     * @param  JobIdInterface $job
     * @param  QueueTask $task
     * @return void
     */
    private function releaseTask(JobIdInterface $job, QueueTask $task): void
    {
        $this->pheanstalk->release(
            $job,
            $this->getPriority($task->getPriority()),
            $task->getDelay()
        );
    }

    /**
     * Flush all tasks from the queue
     *
     * @param  string|null $queue
     * @return void
     */
    public function flush(?string $queue = null): void
    {
        $queues = $this->getQueuesToFlush($queue);

        foreach ($queues as $queueName) {
            $this->flushQueue($queueName);
        }
    }

    /**
     * Get the list of queues to flush
     *
     * @param  string|null $queue
     * @return array
     */
    private function getQueuesToFlush(?string $queue): array
    {
        if (!is_null($queue)) {
            return [$queue];
        }

        return (array) cache(self::QUEUE_CACHE_KEY) ?: [];
    }

    /**
     * Flush all tasks from a specific queue
     *
     * @param  string $queueName
     * @return void
     */
    private function flushQueue(string $queueName): void
    {
        $this->pheanstalk->useTube(new TubeName($queueName));

        while ($task = $this->pheanstalk->reserveWithTimeout(0)) {
            $this->pheanstalk->delete($task);
        }
    }
}
