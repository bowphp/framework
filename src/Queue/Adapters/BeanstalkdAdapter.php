<?php

declare(strict_types=1);

namespace Bow\Queue\Adapters;

use Pheanstalk\Pheanstalk;
use Bow\Queue\ProducerService;
use Bow\Queue\Adapters\QueueAdapter;
use Pheanstalk\Contract\PheanstalkInterface;
use RuntimeException;

class BeanstalkdAdapter extends QueueAdapter
{
    /**
     * Define the instance Pheanstalk
     *
     * @var Pheanstalk
     */
    private Pheanstalk $pheanstalk;

    /**
     * Determine the default watch name
     *
     * @var string
     */
    private string $default = "default";

    /**
     * @var int
     */
    private int $retry;

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

        $this->pheanstalk = Pheanstalk::create($queue["hostname"], $queue["port"], $queue["timeout"]);

        return $this;
    }

    /**
     * Get connexion
     *
     * @param string $name
     * @return Pheanstalk
     */
    public function setWatch(string $name): void
    {
        $this->default = $name;
    }

    /**
     * Get connexion
     *
     * @param int $retry
     * @return Pheanstalk
     */
    public function setRetry(int $retry): void
    {
        $this->retry = $retry;
    }

    /**
     * Get the queue or return the default.
     *
     * @param  ?string $queue
     * @return string
     */
    public function getQueue(?string $queue = null): string
    {
        return $queue ?: $this->default;
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
     * @return QueueAdapter
     */
    public function push(ProducerService $producer): void
    {
        $this->pheanstalk
            ->useTube($producer->getQueue())
            ->put(serialize($producer), $producer->getDelay(), $producer->getRetry());
    }

    /**
     * Run the worker
     *
     * @param string|null $queue
     * @return mixed
     */
    public function run(string $queue = null): void
    {
        // we want jobs from 'testtube' only.
        $queue = $this->getQueue($queue);
        $this->pheanstalk->watch($queue);

        // This hangs until a Job is produced.
        $job = $this->pheanstalk->reserveWithTimeout(50);

        if (is_null($job)) {
            return;
        }

        try {
            $payload = $job->getData();
            /**@var ProducerService */
            $producer = unserialize($payload);
            $delay = $producer->getDelay();
            call_user_func([$producer, "process"]);
            $this->pheanstalk->touch($job);
            $this->pheanstalk->delete($job);
        } catch (\Exception $e) {
            error_log($e->getMessage());
            app('logger')->error($e->getMessage(), $e->getTrace());
            cache("failed:job:" . $job->getId(), $job->getData());
            if ($producer->jobShouldBeDelete()) {
                $this->pheanstalk->delete($job);
            } else {
                $this->pheanstalk->release($job, PheanstalkInterface::DEFAULT_PRIORITY, $delay);
            }
        }
    }
}
