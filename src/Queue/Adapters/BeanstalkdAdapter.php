<?php

declare(strict_types=1);

namespace Bow\Queue\Adapters;

use RuntimeException;
use Pheanstalk\Pheanstalk;
use Bow\Queue\ProducerService;
use Bow\Queue\Adapters\QueueAdapter;
use Pheanstalk\Contract\PheanstalkInterface;

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
     * @param array $queue
     * @return mixed
     */
    public function configure(array $queue): BeanstalkdAdapter
    {
        if (!class_exists(Pheanstalk::class)) {
            throw new RuntimeException("Please install the pda/pheanstalk package");
        }

        $this->pheanstalk = Pheanstalk::create(
            $queue["hostname"],
            $queue["port"],
            $queue["timeout"]
        );

        if (isset($queue["queue"])) {
            $this->setQueue($queue["queue"]);
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
        $queue = $this->getQueue($queue);

        return (int) $this->pheanstalk->statsTube($queue)->current_jobs_ready;
    }

    /**
     * Queue a job
     *
     * @param ProducerService $producer
     * @return void
     */
    public function push(ProducerService $producer): void
    {
        $queues = (array) cache("beanstalkd:queues");

        if (!in_array($producer->getQueue(), $queues)) {
            $queues[] = $producer->getQueue();
            cache("beanstalkd:queues", $queues);
        }

        $this->pheanstalk
            ->useTube($producer->getQueue())
            ->put(
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
     */
    public function run(string $queue = null): void
    {
        // we want jobs from define queue only.
        $queue = $this->getQueue($queue);
        $this->pheanstalk->watch($queue);

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
            // and let developper to decide if the job should be delete
            $producer->onException($e);

            // Check if the job should be delete
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
     * @return void
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
        switch ($priority) {
            case $priority > 2:
                return 4294967295;
            case 1:
                return PheanstalkInterface::DEFAULT_PRIORITY;
            case 0:
                return 0;
            default:
                return PheanstalkInterface::DEFAULT_PRIORITY;
        }
    }
}
