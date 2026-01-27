<?php

declare(strict_types=1);

namespace Bow\Queue\Adapters;

use Bow\Queue\QueueTask;
use Pheanstalk\Contract\PheanstalkPublisherInterface;
use Pheanstalk\Contract\JobIdInterface;
use Pheanstalk\Pheanstalk;
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
     * Push a job onto the queue
     *
     * @param  QueueTask $producer
     * @return bool
     */
    public function push(QueueTask $producer): bool
    {
        $this->registerQueueName($producer->getQueue());

        $this->pheanstalk->useTube(new TubeName($producer->getQueue()));

        $this->pheanstalk->put(
            $this->serializeProducer($producer),
            $this->getPriority($producer->getPriority()),
            $producer->getDelay(),
            $producer->getRetry()
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
        $queueName = $this->getQueue($queue);
        $this->pheanstalk->watch(new TubeName($queueName));

        $job = null;
        $producer = null;

        try {
            $job = $this->pheanstalk->reserve();
            $producer = $this->unserializeProducer($job->getData());

            $this->executeTask($producer);
            $this->pheanstalk->touch($job);
            $this->pheanstalk->delete($job);
            $this->updateProcessingTimeout();
        } catch (Throwable $e) {
            $this->handleJobFailure($job, $producer, $e);
        }
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
     * @param  JobIdInterface|null $job
     * @param  QueueTask|null $producer
     * @param  Throwable $exception
     * @return void
     */
    private function handleJobFailure(?JobIdInterface $job, ?QueueTask $producer, Throwable $exception): void
    {
        $this->logError($exception);

        if (is_null($job)) {
            return;
        }

        cache("job:failed:" . $job->getId(), $job->getData());

        if (is_null($producer)) {
            $this->pheanstalk->delete($job);
            return;
        }

        $producer->onException($exception);

        if ($producer->taskShouldBeDelete()) {
            $this->pheanstalk->delete($job);
        } else {
            $this->releaseJob($job, $producer);
        }

        $this->sleep(1);
    }

    /**
     * Release the job back to the queue for retry
     *
     * @param  JobIdInterface $job
     * @param  QueueTask $producer
     * @return void
     */
    private function releaseJob(JobIdInterface $job, QueueTask $producer): void
    {
        $this->pheanstalk->release(
            $job,
            $this->getPriority($producer->getPriority()),
            $producer->getDelay()
        );
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
     * Flush all jobs from the queue
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
     * Flush all jobs from a specific queue
     *
     * @param  string $queueName
     * @return void
     */
    private function flushQueue(string $queueName): void
    {
        $this->pheanstalk->useTube(new TubeName($queueName));

        while ($job = $this->pheanstalk->reserveWithTimeout(0)) {
            $this->pheanstalk->delete($job);
        }
    }
}
