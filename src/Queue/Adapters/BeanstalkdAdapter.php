<?php

declare(strict_types=1);

namespace Bow\Queue\Adapters;

use Bow\Queue\QueueTask;
use Pheanstalk\Contract\JobIdInterface;
use Pheanstalk\Contract\PheanstalkPublisherInterface;
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
        $queueName = $this->getQueue($queue);
        $this->pheanstalk->watch(new TubeName($queueName));

        $task = null;
        $job = null;

        try {
            $job = $this->pheanstalk->reserve();
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
     * @param  JobIdInterface|null $job
     * @param  QueueTask|null $task
     * @param  Throwable $exception
     * @return void
     */
    private function handleTaskFailure(?JobIdInterface $job, ?QueueTask $task, Throwable $exception): void
    {
        $this->logError($exception);

        $this->logFailedTask($task, $exception);

        if (is_null($job)) {
            return;
        }

        cache("task:failed:" . $task->getId(), method_exists($task, 'getData') ? $task->getData() : "");

        if (is_null($task)) {
            $this->pheanstalk->delete($job);
            return;
        }

        $task->onException($exception);

        if ($task->taskShouldBeDelete()) {
            $this->pheanstalk->delete($job);
        } else {
            $this->releaseTask($job, $task);
        }

        $this->sleep(1);
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
