<?php

namespace Bow\Queue\Adapters;

use Bow\Database\Database;
use Bow\Database\Exception\QueryBuilderException;
use Bow\Database\QueryBuilder;
use Bow\Queue\ProducerService;
use ErrorException;
use Exception;

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
     * @param array $config
     * @return mixed
     */
    public function configure(array $config): DatabaseAdapter
    {
        $this->table = Database::table($config["table"] ?? "queue_jobs");

        return $this;
    }

    /**
     * Get the size of the queue.
     *
     * @param string|null $queue
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
     * Queue a job
     *
     * @param ProducerService $producer
     * @return void
     */
    public function push(ProducerService $producer): void
    {
        $this->table->insert([
            "id" => $this->generateId(),
            "queue" => $this->getQueue(),
            "payload" => base64_encode($this->serializeProducer($producer)),
            "attempts" => $this->tries,
            "status" => "waiting",
            "available_at" => date("Y-m-d H:i:s", time() + $producer->getDelay()),
            "reserved_at" => null,
            "created_at" => date("Y-m-d H:i:s"),
        ]);
    }

    /**
     * Run the worker
     *
     * @param string|null $queue
     * @return void
     * @throws QueryBuilderException
     * @throws ErrorException
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
                if (strtotime($job->available_at) >= time()) {
                    if (!is_null($job->reserved_at) && strtotime($job->reserved_at) < time()) {
                        continue;
                    }
                    $this->table->where("id", $job->id)->update([
                        "status" => "processing",
                    ]);
                    $this->execute($producer, $job);
                    continue;
                }
            } catch (Exception $e) {
                // Write the error log
                error_log($e->getMessage());
                app('logger')->error($e->getMessage(), $e->getTrace());
                cache("job:failed:" . $job->id, $job->payload);

                // Check if producer has been loaded
                if (!isset($producer)) {
                    $this->sleep(1);
                    continue;
                }

                // Execute the onException method for notify the producer
                // and let developer decide if the job should be deleted
                $producer->onException($e);

                // Check if the job should be deleted
                if ($producer->jobShouldBeDelete() || $job->attempts <= 0) {
                    $this->table->where("id", $job->id)->update([
                        "status" => "failed",
                    ]);
                    $this->sleep(1);
                    continue;
                }

                // Check if the job should be retried
                $this->table->where("id", $job->id)->update([
                    "status" => "reserved",
                    "attempts" => $job->attempts - 1,
                    "available_at" => date("Y-m-d H:i:s", time() + $producer->getDelay()),
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
     * @throws QueryBuilderException
     */
    private function execute(ProducerService $producer, mixed $job): void
    {
        call_user_func([$producer, "process"]);
        $this->table->where("id", $job->id)->update([
            "status" => "done"
        ]);
        $this->sleep($this->sleep ?? 5);
    }

    /**
     * Flush the queue table
     *
     * @param ?string $queue
     * @return void
     * @throws QueryBuilderException
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
