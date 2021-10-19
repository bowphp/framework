<?php

namespace Bow\Queue;

use Bow\Queue\Adapters\QueueAdapter;
use Bow\Queue\Adapters\BeanstalkdAdapter;

class Connection
{
    /**
     * The configuration array
     *
     * @var array
     */
    private $config;

    /**
     * The configuration array
     *
     * @var string
     */
    private $connection = "beanstalkd";

    /**
     * The supported connection
     * 
     * @param array
     */
    private $connections = [
        "beanstalkd" => BeanstalkdAdapter::class,
        "sync" => BeanstalkdAdapter::class,
    ];

    /**
     * Configuration of worker connection
     *
     * @param array $config
     * @return QueueAdapter
     */
    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * Set connection
     * 
     * @param string $connection
     */
    public function setConnection(string $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Get the define adapter
     *
     * @return QueueAdapter
     */
    public function getAdapter()
    {
        $driver = $this->connection ?: $this->config["default"];
        $connection = $this->config["connections"][$driver];
        $queue = new $this->connections[$driver];

        return $queue->configure($connection);
    }
}
