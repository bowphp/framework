<?php

namespace Bow\Queue;

namespace Bow\Queue\Adapters\BeanstalkdAdapter;

class ServiceWorker
{
    /**
     * The supported connection
     * 
     * @param array
     */
    private $connection = [
        "beanstalkd" => BeanstalkdAdapter::class,
        "rabbitmq" => BeanstalkdAdapter::class,
        "sqs" => BeanstalkdAdapter::class,
    ];

    /**
     * Make connection base on default name
     * 
     * @param string $name
     * @return 
     */
    public function connection(string $name)
    {
        $connection = $this->connection[$name];
    }

    /**
     * Start the consumer
     *
     * @param string $queue_name
     * @param integer $retry
     * @return void
     */
    public function run(string $queue_name = "default", int $retry = 60)
    {

    }
}
