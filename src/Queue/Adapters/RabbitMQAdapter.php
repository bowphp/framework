<?php

declare(strict_types=1);

namespace Bow\Queue\Adapters;

use Bow\Queue\QueueTask;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class RabbitMQAdapter extends QueueAdapter
{
    /**
     * @var AMQPStreamConnection|null
     */
    protected ?AMQPStreamConnection $connection = null;

    /**
     * @var \PhpAmqpLib\Channel\AMQPChannel|null
     */
    protected $channel = null;

    /**
     * @var array
     */
    protected array $config = [];

    /**
     * Configure the adapter
     *
     * @param array $config
     * @return QueueAdapter
     */
    public function configure(array $config): QueueAdapter
    {
        $this->config = $config;
        $host = $config['host'] ?? 'localhost';
        $port = $config['port'] ?? 5672;
        $user = $config['user'] ?? 'guest';
        $password = $config['password'] ?? 'guest';
        $vhost = $config['vhost'] ?? '/';
        $queue = $config['queue'] ?? 'default';
        $this->queue = $queue;

        $this->connection = new AMQPStreamConnection($host, $port, $user, $password, $vhost);
        $this->channel = $this->connection->channel();
        $this->channel->queue_declare($this->queue, false, true, false, false);
        return $this;
    }

    /**
     * Push a new job onto the queue
     *
     * @param QueueTask $job
     * @return bool
     */
    public function push(QueueTask $job): bool
    {
        $body = $this->serializeProducer($job);
        $msg = new AMQPMessage($body, [
            'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT
        ]);
        $this->channel->basic_publish($msg, '', $this->queue);
        return true;
    }

    /**
     * Run the worker to consume jobs
     *
     * @param string|null $queue
     * @return void
     */
    public function run(?string $queue = null): void
    {
        $queue = $this->getQueue($queue);
        $callback = function ($msg) {
            $job = $this->unserializeProducer($msg->body);
            try {
                error_log('Processing job: ' . get_class($job) . ' with ID: ' . (method_exists($job, 'getId') ? $job->getId() : 'unknown'));
                if (method_exists($job, 'process')) {
                    $job->process();
                } else {
                    throw new \RuntimeException('Job does not have a process or handle method.');
                }
                $msg->ack();
            } catch (\Throwable $e) {
                error_log('Job failed: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
                // Optionally requeue: set second param to true to requeue
                $msg->nack(false, false); // reject and don't requeue
            }
        };
        $this->channel->basic_qos(null, 1, null);
        $this->channel->basic_consume($queue, '', false, false, false, false, $callback);
        while ($this->channel->is_consuming()) {
            $this->channel->wait();
        }
    }

    /**
     * Get the queue size
     *
     * @param string|null $queue
     * @return int
     */
    public function size(?string $queue = null): int
    {
        $queue = $this->getQueue($queue);
        list($queue, $messageCount, $consumerCount) = $this->channel->queue_declare($queue, true);
        return $messageCount;
    }

    /**
     * Flush the queue
     *
     * @param string|null $queue
     * @return void
     */
    public function flush(?string $queue = null): void
    {
        $queue = $this->getQueue($queue);
        $this->channel->queue_purge($queue);
    }

    /**
     * Set the queue name
     *
     * @param string $queue
     * @return void
     */
    public function setQueue(string $queue): void
    {
        $this->queue = $queue;
        if ($this->channel) {
            $this->channel->queue_declare($queue, false, true, false, false);
        }
    }

    /**
     * Destructor to close connections
     */
    public function __destruct()
    {
        if ($this->channel) {
            $this->channel->close();
        }
        if ($this->connection) {
            $this->connection->close();
        }
    }
}
