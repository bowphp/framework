<?php

declare(strict_types=1);

namespace Bow\Queue\Adapters;

use RuntimeException;
use Pheanstalk\Pheanstalk;
use Bow\Queue\ProducerService;
use Pheanstalk\Contract\PheanstalkPublisherInterface;

class BeanstalkdAdapter extends QueueAdapter
{
    /**
     * Define the instance Pheanstalk
     *
     * @var Pheanstalk
     */
    private Pheanstalk $pheanstalk;

    /**
     * Configure Beanstalkd driver
     *
     * @param array $config
     * @return mixed
     */
    public function configure(array $config): BeanstalkdAdapter
    {
        if (!class_exists(Pheanstalk::class)) {
            throw new RuntimeException("Please install the pda/pheanstalk package");
        }

        $this->pheanstalk = Pheanstalk::create(
            $config["hostname"],
            $config["port"],
            $config["timeout"] ? new \Pheanstalk\Values\Timeout($config["timeout"]) : null,
        );

        if (isset($config["queue"])) {
            $this->setQueue($config["queue"]);
        }

        return $this;
    }

    /**
     * Get the size of the queue.
     *
     * @param string $queue
     * @return int
     */
    public function size(?string $queue = null): int
    {
        $queue = new \Pheanstalk\Values\TubeName($this->getQueue($queue));

        return (int) $this->pheanstalk->statsTube($queue)->currentJobsReady;
    }

    /**
     * Queue a job
     *
     * @param ProducerService $producer
     * @return void
     * @throws \ErrorException
     */
    public function push(ProducerService $producer): void
    {
        $queues = (array) cache("beanstalkd:queues");

        if (!in_array($producer->getQueue(), $queues)) {
            $queues[] = $producer->getQueue();
            cache("beanstalkd:queues", $queues);
        }

        $this->pheanstalk
            ->useTube(new \Pheanstalk\Values\TubeName($producer->getQueue()));

        $this->pheanstalk->put(
            $this->serializeProducer($producer),
            $this->getPriority($producer->getPriority()),
            $producer->getDelay(),
            $producer->getRetry()
        );
    }

    /**
     * Run the worker
     *
     * @param string|null $queue
     * @return mixed
     * @throws \ErrorException
     */
    public function run(string $queue = null): void
    {
        // we want jobs from define queue only.
        $queue = $this->getQueue($queue);
        $this->pheanstalk->watch(new \Pheanstalk\Values\TubeName($queue));

        // This hangs until a Job is produced.
        $job = $this->pheanstalk->reserve();

        if (is_null($job)) {
            sleep($this->sleep ?? 5);
            return;
        }

        try {
            $payload = $job->getData();
            $producer = $this->unserializeProducer($payload);
            call_user_func([$producer, "process"]);
            $this->sleep(2);
            $this->pheanstalk->touch($job);
            $this->sleep(2);
            $this->pheanstalk->delete($job);
        } catch (\Throwable $e) {
            // Write the error log
            error_log($e->getMessage());
            app('logger')->error($e->getMessage(), $e->getTrace());
            cache("job:failed:" . $job->getId(), $job->getData());

            // Check if producer has been loaded
            if (!isset($producer)) {
                $this->pheanstalk->delete($job);
                return;
            }

            // Execute the onException method for notify the producer
            // and let developer decide if the job should be deleted
            $producer->onException($e);

            // Check if the job should be deleted
            if ($producer->jobShouldBeDelete()) {
                $this->pheanstalk->delete($job);
            } else {
                $this->pheanstalk->release($job, $this->getPriority($producer->getPriority()), $producer->getDelay());
            }

            $this->sleep(1);
        }
    }

    /**
     * Flush the queue
     *
     * @param string|null $queue
     * @return void
     * @throws \ErrorException
     */
    public function flush(?string $queue = null): void
    {
        $queues = (array) $queue;

        if (count($queues) == 0) {
            $queues = cache("beanstalkd:queues");
        }

        foreach ($queues as $queue) {
            $this->pheanstalk->useTube($queue);

            while ($job = $this->pheanstalk->reserve()) {
                $this->pheanstalk->delete($job);
            }
        }
    }

    /**
     * Get the priority
     *
     * @param int $priority
     * @return int
     */
    public function getPriority(int $priority): int
    {
        return match ($priority) {
            $priority > 2 => 4294967295,
            1 => PheanstalkPublisherInterface::DEFAULT_PRIORITY,
            0 => 0,
            default => PheanstalkPublisherInterface::DEFAULT_PRIORITY,
        };
    }
}
