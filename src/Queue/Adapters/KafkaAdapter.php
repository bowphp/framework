<?php

declare(strict_types=1);

namespace Bow\Queue\Adapters;

use Bow\Queue\QueueTask;
use RdKafka\Conf;
use RdKafka\Consumer;
use RdKafka\Producer;
use RdKafka\TopicConf;

class KafkaAdapter extends QueueAdapter
{
    /**
     * @var Producer|null
     */
    protected ?Producer $producer = null;

    /**
     * @var Consumer|null
     */
    protected ?Consumer $consumer = null;

    /**
     * @var array
     */
    protected array $config = [];

    /**
     * @var string
     */
    protected string $topic = 'default';

    /**
     * @var string
     */
    protected string $group_id = 'bow_queue_group';

    /**
     * Configure the adapter
     *
     * @param array $config
     * @return QueueAdapter
     */
    public function configure(array $config): QueueAdapter
    {
        if (!extension_loaded('rdkafka')) {
            throw new \RuntimeException("Please install the rdkafka extension");
        }

        $this->config = $config;
        $this->topic = $config['topic'] ?? $config['queue'] ?? 'default';
        $this->queue = $this->topic;
        $this->group_id = $config['group_id'] ?? 'bow_queue_group';

        $this->initProducer();
        $this->initConsumer();

        return $this;
    }

    /**
     * Initialize the Kafka producer
     *
     * @return void
     */
    protected function initProducer(): void
    {
        $conf = new Conf();
        $conf->set('metadata.broker.list', $this->getBrokers());

        if (isset($this->config['security_protocol'])) {
            $conf->set('security.protocol', $this->config['security_protocol']);
        }

        if (isset($this->config['sasl_mechanisms'])) {
            $conf->set('sasl.mechanisms', $this->config['sasl_mechanisms']);
        }

        if (isset($this->config['sasl_username'])) {
            $conf->set('sasl.username', $this->config['sasl_username']);
        }

        if (isset($this->config['sasl_password'])) {
            $conf->set('sasl.password', $this->config['sasl_password']);
        }

        $this->producer = new Producer($conf);
    }

    /**
     * Initialize the Kafka consumer
     *
     * @return void
     */
    protected function initConsumer(): void
    {
        $conf = new Conf();
        $conf->set('metadata.broker.list', $this->getBrokers());
        $conf->set('group.id', $this->group_id);
        $conf->set('auto.offset.reset', $this->config['auto_offset_reset'] ?? 'earliest');
        $conf->set('enable.auto.commit', $this->config['enable_auto_commit'] ?? 'true');

        if (isset($this->config['security_protocol'])) {
            $conf->set('security.protocol', $this->config['security_protocol']);
        }

        if (isset($this->config['sasl_mechanisms'])) {
            $conf->set('sasl.mechanisms', $this->config['sasl_mechanisms']);
        }

        if (isset($this->config['sasl_username'])) {
            $conf->set('sasl.username', $this->config['sasl_username']);
        }

        if (isset($this->config['sasl_password'])) {
            $conf->set('sasl.password', $this->config['sasl_password']);
        }

        $this->consumer = new Consumer($conf);
    }

    /**
     * Get broker list from config
     *
     * @return string
     */
    protected function getBrokers(): string
    {
        if (isset($this->config['brokers'])) {
            return is_array($this->config['brokers'])
                ? implode(',', $this->config['brokers'])
                : $this->config['brokers'];
        }

        $host = $this->config['host'] ?? 'localhost';
        $port = $this->config['port'] ?? 9092;

        return "{$host}:{$port}";
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

        $topic = $this->producer->newTopic($this->topic);
        $body = $this->serializeProducer($task);

        $topic->produce(RD_KAFKA_PARTITION_UA, 0, $body);
        $this->producer->poll(0);

        // Wait for message to be sent
        $result = $this->producer->flush(10000);

        return $result === RD_KAFKA_RESP_ERR_NO_ERROR;
    }

    /**
     * Run the worker to consume tasks
     *
     * @param string|null $queue
     * @return void
     */
    public function run(?string $queue = null): void
    {
        $topic_name = $queue ?? $this->topic;
        $topic = $this->consumer->newTopic($topic_name, $this->getTopicConf());

        // Start consuming from partition 0, at the stored offset
        $topic->consumeStart(0, RD_KAFKA_OFFSET_STORED);

        $message = $topic->consume(0, $this->timeout * 1000);

        if ($message === null) {
            return;
        }

        switch ($message->err) {
            case RD_KAFKA_RESP_ERR_NO_ERROR:
                $this->processMessage($message);
                break;

            case RD_KAFKA_RESP_ERR__PARTITION_EOF:
                // Reached end of partition, wait for more messages
                $this->sleep($this->sleep ?: 1);
                break;

            case RD_KAFKA_RESP_ERR__TIMED_OUT:
                // Timeout, continue waiting
                break;

            default:
                error_log('Kafka error: ' . $message->errstr());
                break;
        }
    }

    /**
     * Process a consumed message
     *
     * @param \RdKafka\Message $message
     * @return void
     */
    protected function processMessage($message): void
    {
        try {
            $task = $this->unserializeProducer($message->payload);

            $this->logProcesingTask($task);

            if (method_exists($task, 'process')) {
                $task->process();
                $this->logProcessedTask($task);
            } else {
                throw new \RuntimeException('Job does not have a process method.');
            }
        } catch (\Throwable $e) {
            $this->logFailedTask($task ?? null, $e);
        }
    }

    /**
     * Get topic configuration
     *
     * @return TopicConf
     */
    protected function getTopicConf(): TopicConf
    {
        $topic_conf = new TopicConf();
        $topic_conf->set('auto.offset.reset', $this->config['auto_offset_reset'] ?? 'earliest');

        return $topic_conf;
    }

    /**
     * Get the queue size
     *
     * @param string|null $queue
     * @return int
     */
    public function size(?string $queue = null): int
    {
        // Kafka doesn't have a direct way to get queue size like traditional queues
        // This would require querying the broker for partition offsets
        // Returning 0 as a placeholder
        return 0;
    }

    /**
     * Flush the queue
     *
     * @param string|null $queue
     * @return void
     */
    public function flush(?string $queue = null): void
    {
        // Kafka topics cannot be easily flushed like traditional queues
        // This would require deleting and recreating the topic
        // or using retention policies
        error_log('Warning: Kafka topics cannot be flushed directly. Use topic retention policies instead.');
    }

    /**
     * Set the queue/topic name
     *
     * @param string $queue
     * @return void
     */
    public function setQueue(string $queue): void
    {
        $this->queue = $queue;
        $this->topic = $queue;
    }
}
