<?php

namespace Bow\Queue\Adapters;

use Bow\Database\Database;
use Bow\Database\QueryBuilder;
use Bow\Queue\ProducerService;
use RuntimeException;

class DatabaseAdapter extends QueueAdapter
{
    /**
     * Define the instance Pheanstalk
     *
     * @var QueryBuilder
     */
    private QueryBuilder $table;

    /**
     * Determine the default watch name
     *
     * @var string
     */
    private string $queue = "default";

    /**
     * The number of working attempts
     *
     * @var int
     */
    private int $tries;

    /**
     * Define the sleep time
     *
     * @var int
     */
    private int $sleep = 5;

    /**
     * Configure Beanstalkd driver
     *
     * @param array $queue
     * @return mixed
     */
    public function configure(array $queue): DatabaseAdapter
    {
        $this->table = Database::table($queue["table"] ?? "queue_jobs");

        return $this;
    }

    /**
     * Get connexion
     *
     * @param string $name
     * @return void
     */
    public function setWatch(string $name): void
    {
        $this->queue = $name;
    }

    /**
     * Set job tries
     *
     * @param int $tries
     * @return void
     */
    public function setTries(int $tries): void
    {
        $this->tries = $tries;
    }

    /**
     * Set sleep time
     *
     * @param int $sleep
     * @return void
     */
    public function setSleep(int $sleep): void
    {
        $this->sleep = $sleep;
    }

    /**
     * Get the queue or return the default.
     *
     * @param  ?string $queue
     * @return string
     */
    public function getQueue(?string $queue = null): string
    {
        return $queue ?: $this->queue;
    }

    /**
     * Get the size of the queue.
     *
     * @param string $queue
     * @return int
     */
    public function size(?string $queue = null): int
    {
        return $this->table
            ->where("queue", $this->getQueue($queue))
            ->count();
    }

    /**
     * Queue a job
     *
     * @param ProducerService $producer
     * @return QueueAdapter
     */
    public function push(ProducerService $producer): void
    {
        $this->table->insert([
            "id" => $producer->getId(),
            "queue" => $this->getQueue(),
            "payload" => $this->serializeProducer($producer),
            "attempts" => $producer->getRetry(),
            "status" => "pending",
            "available_at" => date("Y-m-d H:i:s", time() + $producer->getDelay()),
            "reserved_at" => null,
            "created_at" => date("Y-m-d H:i:s"),
        ]);
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
        $queues = $this->table
            ->where("queue", $queue)
            ->whereIn("status", ["pending", "reserved"])
            ->get();

        if (count($queues) == 0) {
            $this->sleep($this->sleep ?? 5);
            return;
        }

        foreach ($queues as $job) {
            try {
                $producer = $this->unserializeProducer($job->payload);
                $delay = $producer->getDelay();
                if ($job->delay == 0) {
                    $this->execute($producer, $job);
                    continue;
                }
                $execute_time = time() + $job->delay;
                if ($execute_time >= time()) {
                    $this->execute($producer, $job);
                }
            } catch (\Exception $e) {
                error_log($e->getMessage());
                app('logger')->error($e->getMessage(), $e->getTrace());
                cache("failed:job:" . $job->id, $job->payload);
                if ($producer->jobShouldBeDelete() || $job->retry <= 0) {
                    $this->table->where("id", $job->id)->delete();
                    $this->sleep(1);
                    continue;
                }
                $this->table->where("id", $job->id)->update([
                    "status" => "reserved",
                    "retry" => $job->tries - 1,
                    'delay' => $delay
                ]);
                $this->sleep(1);
            }
        }
    }

    /**
     * Process the next job on the queue.
     *
     * @param ProducerService $producer
     * @param mixed $job
     */
    private function execute(ProducerService $producer, mixed $job)
    {
        call_user_func([$producer, "process"]);
        $this->table->where("id", $job->id)->update([
            "status" => "processed"
        ]);
        sleep($this->sleep ?? 5);
    }
}
