<?php

namespace Bow\Queue\Adapters;

use Bow\Database\Database;
use Bow\Database\QueryBuilder;
use Bow\Queue\ProducerService;

class DatabaseAdapter extends QueueAdapter
{
    /**
     * Define the instance Pheanstalk
     *
     * @var QueryBuilder
     */
    private QueryBuilder $table;

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
            "id" => $this->generateId(),
            "queue" => $this->getQueue(),
            "payload" => base64_encode($this->serializeProducer($producer)),
            "attempts" => $this->tries,
            "status" => "waiting",
            "avalaibled_at" => date("Y-m-d H:i:s", time() + $producer->getDelay()),
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
            ->whereIn("status", ["waiting", "reserved"])
            ->get();

        if (count($queues) == 0) {
            $this->sleep($this->sleep ?? 5);
            return;
        }

        foreach ($queues as $job) {
            try {
                $producer = $this->unserializeProducer(base64_decode($job->payload));
                if (strtotime($job->avalaibled_at) >= time()) {
                    if (!is_null($job->reserved_at) && strtotime($job->reserved_at) < time()) {
                        continue;
                    }
                    $this->table->where("id", $job->id)->update([
                        "status" => "processing",
                    ]);
                    $this->execute($producer, $job);
                    continue;
                }
            } catch (\Exception $e) {
                error_log($e->getMessage());
                app('logger')->error($e->getMessage(), $e->getTrace());
                cache("failed:job:" . $job->id, $job->payload);
                if (!isset($producer)) {
                    $this->sleep(1);
                    continue;
                }
                if ($producer->jobShouldBeDelete() || $job->attempts <= 0) {
                    $this->table->where("id", $job->id)->delete();
                    $this->sleep(1);
                    continue;
                }
                $this->table->where("id", $job->id)->update([
                    "status" => "reserved",
                    "attempts" => $job->attempts - 1,
                    "avalaibled_at" => date("Y-m-d H:i:s", time() + $producer->getDelay()),
                    "reserved_at" => date("Y-m-d H:i:s", time() + $producer->getRetry())
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
            "status" => "done"
        ]);
        sleep($this->sleep ?? 5);
    }

    /**
     * Flush the queue table
     *
     * @param ?string $queue
     * @return void
     */
    public function flush(?string $queue = null): void
    {
        if (is_null($queue)) {
            $this->table->truncate();
        } else {
            $this->table->where("queue", $queue)->delete();
        }
    }
}
