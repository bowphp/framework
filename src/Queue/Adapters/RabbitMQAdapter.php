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
        $this->channel->basic_publish($msg, '', $task->getQueue());
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
        $callback = fn ($msg) => $this->processMessage($msg);
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
     * Process a consumed message
     *
     * @param  object $msg
     * @return void
     */
    protected function processMessage(object $msg): void
    {
        $task = null;

        try {
            // unserializeProducer() belongs inside the try: a payload that fails
            // integrity verification, or that names a task class this worker
            // cannot load, throws here. Letting it escape the consumer callback
            // leaves the message neither acked nor nacked, so the broker
            // redelivers it and the worker crash-loops on the same poison bytes.
            $task = $this->unserializeProducer($msg->body);

            $this->logProcessingTask($task);

            if (!method_exists($task, 'process')) {
                throw new RuntimeException('Task does not have a process or handle method.');
            }

            $task->process();
            $this->logProcessedTask($task);
            $msg->ack();
        } catch (\Throwable $e) {
            $this->handleMessageFailure($msg, $task, $e);
        }
    }

    /**
     * Settle a message whose processing failed
     *
     * Mirrors the beanstalkd adapter: the task decides, through onException()
     * and taskShouldBeDelete(), whether the failure is terminal. A transient
     * failure is requeued instead of being dropped on the first throw, and the
     * throttle keeps a permanently failing task from spinning the worker hot.
     *
     * AMQP has no per-message delay without a delayed-exchange plugin, so
     * getDelay() cannot be honoured here; the requeue is immediate.
     *
     * @param  object $msg
     * @param  QueueTask|null $task
     * @param  \Throwable $exception
     * @return void
     */
    private function handleMessageFailure(object $msg, ?QueueTask $task, \Throwable $exception): void
    {
        $this->logFailedTask($task, $exception);

        // Poison message: the body never became a task, so there is no id to key
        // on and nothing to retry — a requeue would redeliver the same bytes for
        // ever. Keep the raw body for inspection and reject it for good.
        if (is_null($task)) {
            $this->recordFailedPayload("task:failed:body:" . md5($msg->body), $msg->body);
            $msg->nack(false, false);

            return;
        }

        $msg->nack(!$this->resolveFailedTask($task, $exception), false);

        $this->sleep(1);
    }

    /**
     * Run the task defined failure handling and decide whether to drop the message
     *
     * onException() and taskShouldBeDelete() are both overridable, so they are
     * user code and may throw. A throw must not escape, otherwise the message is
     * never settled, it redelivers and the worker crash-loops on it.
     *
     * @param  QueueTask $task
     * @param  \Throwable $exception
     * @return bool Whether the message should be dropped
     */
    private function resolveFailedTask(QueueTask $task, \Throwable $exception): bool
    {
        try {
            $this->recordFailedPayload(
                "task:failed:" . $task->getId(),
                method_exists($task, 'getData') ? $task->getData() : ""
            );

            $task->onException($exception);

            return $task->taskShouldBeDelete();
        } catch (\Throwable $taskException) {
            $this->logError($taskException);

            // The task cannot handle its own failure, so retrying it would most
            // likely break the same way. Drop it rather than requeue for ever.
            return true;
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
