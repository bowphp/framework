<?php

declare(strict_types=1);

namespace Bow\Queue\Adapters;

use Pheanstalk\Pheanstalk;
use Bow\Queue\ProducerService;
use Bow\Queue\Adapters\QueueAdapter;
use Pheanstalk\Job as PheanstalkJob;

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
     * Configure Beanstalkd driver
     *
     * @param array $queue
     * @return mixed
     */
    public function configure(array $queue): BeanstalkdAdapter
    {
        $this->pheanstalk = Pheanstalk::create($queue["hostname"], $queue["port"], $queue["timeout"]);

        return $this;
    }

    /**
     * Get connexion
     *
     * @param string $name
     * @return Pheanstalk
     */
    public function setWatch(string $name)
    {
        $this->default = $name;
    }

    /**
     * Get connexion
     *
     * @param int $retry
     * @return Pheanstalk
     */
    public function setRetry(int $retry)
    {
        $this->retry = $retry;
    }

    /**
     * Delete a message from the Beanstalk queue.
     *
     * @param  string  $queue
     * @param  string|int  $id
     * @return void
     */
    public function deleteJob(string $queue, string|int $id): void
    {
        $queue = $this->getQueue($queue);

        $this->pheanstalk->useTube($queue)->delete(new PheanstalkJob($id, ''));
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
            ->put($this->serializeProducer($producer), $producer->getDelay(), $producer->getRetry());
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
        $this->pheanstalk->watch($this->getQueue($queue));

        // This hangs until a Job is produced.
        $job = $this->pheanstalk->reserve();

        try {
            $payload = $job->getData();
            $producer = unserialize($payload);
            call_user_func_array([$producer, "process"], []);
            $this->pheanstalk->touch($job);
            $this->deleteJob($queue, $job->getId());
        } catch (\Exception $e) {
            $this->pheanstalk->release($job);
        }
    }
}
