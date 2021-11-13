<?php

namespace Bow\Queue;

use Bow\Queue\Adapters\QueueAdapter;
use Bow\Queue\Adapters\BeanstalkdAdapter;
use ErrorException;

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
    private static $connections = [
        "beanstalkd" => BeanstalkdAdapter::class,
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
     * Push the new connection support in connectors managment
     *
     * @param string $name
     * @param string $name
     */
    public static function pushConnection(string $name, string $classname)
    {
        if (!array_key_exists($name, static::$connections)) {
            static::$connections[$name] = $classname;

            return true;
        }

        throw new ErrorException(
            "An other connection with some name already exists"
        );
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
        $queue = new static::$connections[$driver];

        return $queue->configure($connection);
    }

    /**
     * __call
     *
     * @param string $name
     * @param array $arguments
     * @return mixed
     */
    public function __call(string $name, array $arguments)
    {
        $adapter = $this->getAdapter();

        if (method_exists($adapter, $name)) {
            return call_user_func_array([$adapter, $name], $arguments);
        }
    }
}
