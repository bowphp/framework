<?php

declare(strict_types=1);

namespace Bow\Queue\Adapters;

use Bow\Queue\QueueTask;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use RuntimeException;

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
        if (!class_exists(AMQPStreamConnection::class)) {
            throw new RuntimeException("Please install the php-amqplib/php-amqplib package");
        }

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
     * Push a new task onto the queue
     *
     * @param QueueTask $task
     * @return bool
     */
    public function push(QueueTask $task): bool
    {
        $task->setId($this->generateId());
        $body = $this->serializeProducer($task);
        $msg = new AMQPMessage($body, [
            'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT
        ]);
        $this->channel->basic_publish($msg, '', $this->queue);
        return true;
    }

    /**
     * Run the worker to consume tasks
     *
     * @param string|null $queue
     * @return void
     */
    public function run(?string $queue = null): void
    {
        $queue = $this->getQueue($queue);
        $callback = function ($msg) {
            $task = $this->unserializeProducer($msg->body);
            try {
                $this->logProcesingTask($task);
                if (method_exists($task, 'process')) {
                    $task->process();
                } else {
                    throw new \RuntimeException('Task does not have a process or handle method.');
                }
                $this->logProcessedTask($task);
                $msg->ack();
            } catch (\Throwable $e) {
                $this->logFailedTask($task, $e);
                // Optionally requeue: set second param to true to requeue
                $msg->nack(false, false); // reject and don't requeue
            }
        };
        $this->channel->basic_qos(null, 1, null);
        $this->channel->basic_consume($queue, '', false, false, false, false, $callback);
        while ($this->channel->is_consuming()) {
            try {
                $this->channel->wait(null, false, 1);
            } catch (\PhpAmqpLib\Exception\AMQPTimeoutException $e) {
                // Timeout reached, check if there are more messages
                if ($this->size($queue) === 0) {
                    break;
                }
            }
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
