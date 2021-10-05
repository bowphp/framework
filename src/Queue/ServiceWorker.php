<?php

namespace Bow\Queue;

namespace Bow\Queue\Adapters\BeanstalkdAdapter;
namespace Bow\Queue\Adapters\QueueAdapter;

class ServiceWorker
{
    /**
     * The supported connection
     * 
     * @param array
     */
    private $connections = [
        "beanstalkd" => BeanstalkdAdapter::class,
        "rabbitmq" => BeanstalkdAdapter::class,
        "sqs" => BeanstalkdAdapter::class,
    ];

    /**
     * The producers collection
     * 
     * @var array
     */
    private $producers = [];

    /**
     * Determine the instance of QueueAdapter
     * 
     * @var QueueAdapter
     */
    private $connection;

    /**
     * Make connection base on default name
     * 
     * @param string $name
     * @return QueueAdapter
     */
    public function connection(string $name)
    {
        return new $this->connections[$name];
    }

    /**
     * Start the consumer
     *
     * @param string $queue_name
     * @param integer $retry
     * @return void
     */
    public function run(QueueAdapter $queue, string $queue_name = "default", int $retry = 60)
    {
        $this->queue->setWatch($queue_name);

        while(true) {
            $this->queue->run();
        }
    }

    /**
     * Dispatch queue
     * 
     * @param ServiceProducer $producer
     * @return mixed
     */
    public function dispatch(ServiceProducer $producer)
    {
        $queue = $service->getQueue();
        $priority = $producer->getPriority();

        if (!isset($this->producers[$queue])) {
            $this->producers[$queue] = [];
        }

        if (!isset($this->producers[$queue][$priority])) {
            $this->producers[$queue][$priority] = [];
        }

        $this->producers[$queue][$priority][] = $producer;
    }
}
