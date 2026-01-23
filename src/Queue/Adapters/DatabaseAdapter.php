<?php

namespace Bow\Queue\Adapters;

use Bow\Database\Database;
use Bow\Database\Exception\QueryBuilderException;
use Bow\Database\QueryBuilder;
use Bow\Queue\QueueTask;
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
     * @param  array $config
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
     * @param  string|null $queue
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
     * @param  QueueTask $job
     * @return void
     */
    public function push(QueueTask $job): bool
    {
        $value = [
            "id" => $this->generateId(),
            "queue" => $this->getQueue(),
            "payload" => base64_encode($this->serializeProducer($job)),
            "attempts" => $this->tries,
            "status" => "waiting",
            "available_at" => date("Y-m-d H:i:s", time() + $job->getDelay()),
            "reserved_at" => null,
            "created_at" => date("Y-m-d H:i:s"),
        ];

        $count = $this->table->insert($value);

        return $count > 0;
    }

    /**
     * Run the worker
     *
     * @param  string|null $queue
     * @return void
     * @throws QueryBuilderException
     * @throws ErrorException
     */
    public function run(?string $queue = null): void
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

        foreach ($queues as $queue) {
            try {
                $producer = $this->unserializeProducer(base64_decode($queue->payload));
                if (strtotime($queue->available_at) >= time()) {
                    if (!is_null($queue->reserved_at) && strtotime($queue->reserved_at) < time()) {
                        continue;
                    }
                    $this->table->where("id", $queue->id)->update([
                        "status" => "processing",
                    ]);
                    $this->execute($producer, $queue);
                    continue;
                }
            } catch (Exception $e) {
                // Write the error log
                error_log($e->getMessage());
                app('logger')->error($e->getMessage(), $e->getTrace());
                cache("job:failed:" . $queue->id, $queue->payload);

                // Check if producer has been loaded
                if (!isset($producer)) {
                    $this->sleep(1);
                    continue;
                }

                // Execute the onException method for notify the producer
                // and let developer decide if the job should be deleted
                $producer->onException($e);

                // Check if the job should be deleted
                if ($producer->jobShouldBeDelete() || $queue->attempts <= 0) {
                    $this->table->where("id", $queue->id)->update([
                        "status" => "failed",
                    ]);
                    $this->sleep(1);
                    continue;
                }

                // Check if the job should be retried
                $this->table->where("id", $queue->id)->update([
                    "status" => "reserved",
                    "attempts" => $queue->attempts - 1,
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
     * @param  QueueTask $job
     * @param  mixed $queue
     * @throws QueryBuilderException
     */
    private function execute(QueueTask $job, mixed $queue): void
    {
        call_user_func([$job, "process"]);
        $this->table->where("id", $queue->id)->update([
            "status" => "done"
        ]);
        $this->sleep($this->sleep ?? 5);
    }

    /**
     * Flush the queue table
     *
     * @param  ?string $queue
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
