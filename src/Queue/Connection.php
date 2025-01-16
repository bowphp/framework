<?php

declare(strict_types=1);

namespace Bow\Queue;

use Bow\Queue\Adapters\QueueAdapter;
use Bow\Queue\Adapters\BeanstalkdAdapter;
use Bow\Queue\Adapters\DatabaseAdapter;
use Bow\Queue\Adapters\SQSAdapter;
use Bow\Queue\Adapters\SyncAdapter;
use ErrorException;

class Connection
{
    /**
     * The configuration array
     *
     * @var array
     */
    private array $config;

    /**
     * The configuration array
     *
     * @var string
     */
    private string $connection = "beanstalkd";

    /**
     * The supported connection
     *
     * @var array
     */
    private static array $connections = [
        "beanstalkd" => BeanstalkdAdapter::class,
        "sqs" => SQSAdapter::class,
        "database" => DatabaseAdapter::class,
        "sync" => SyncAdapter::class,
    ];

    /**
     * Configuration of worker connection
     *
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * Push the new connection support in connectors management
     *
     * @param string $name
     * @param string $classname
     * @return bool
     * @throws ErrorException
     */
    public static function pushConnection(string $name, string $classname): bool
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
     * @return Connection
     */
    public function setConnection(string $connection): Connection
    {
        $this->connection = $connection;

        return $this;
    }

    /**
     * Get the define adapter
     *
     * @return QueueAdapter
     */
    public function getAdapter(): QueueAdapter
    {
        $driver = $this->connection ?: $this->config["default"];

        $connection = $this->config["connections"][$driver];

        $queue = new static::$connections[$driver]();

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
