<?php

declare(strict_types=1);

namespace Bow\Queue;

use Bow\Queue\Adapters\SQSAdapter;
use Bow\Queue\Adapters\SyncAdapter;
use Bow\Queue\Adapters\QueueAdapter;
use Bow\Queue\Adapters\RedisAdapter;
use Bow\Queue\Adapters\KafkaAdapter;
use Bow\Queue\Adapters\DatabaseAdapter;
use Bow\Queue\Adapters\BeanstalkdAdapter;
use Bow\Queue\Adapters\RabbitMQAdapter;
use Bow\Queue\Exceptions\ConnexionException;
use Bow\Queue\Exceptions\MethodCallException;

class Connection
{
    /**
     * The supported connection
     *
     * @var array
     */
    /**
     * Supported connection drivers and their adapter classes
     */
    private const SUPPORTED_CONNECTIONS = [
        'beanstalkd' => BeanstalkdAdapter::class,
        'sqs'        => SQSAdapter::class,
        'database'   => DatabaseAdapter::class,
        'sync'       => SyncAdapter::class,
        'redis'      => RedisAdapter::class,
        'rabbitmq'   => RabbitMQAdapter::class,
        'kafka'      => KafkaAdapter::class,
    ];

    /**
     * The registered connections (can be extended at runtime)
     *
     * @var array
     */
    private static array $connections = self::SUPPORTED_CONNECTIONS;
    /**
     * The queue configuration array
     *
     * @var array
     */
    private array $config;

    /**
     * The selected connection driver name
     *
     * @var ?string
     */
    private ?string $connection = null;

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
     * @param  string $name
     * @param  string $classname
     * @return bool
     * @throws ConnexionException
     */
    /**
     * Register a new connection adapter at runtime
     *
     * @param  string $name
     * @param  string $classname
     * @return bool
     * @throws ConnexionException
     */
    public static function pushConnection(string $name, string $classname): bool
    {
        if (!array_key_exists($name, static::$connections)) {
            static::$connections[$name] = $classname;
            return true;
        }
        throw new ConnexionException(
            "Another connection with the same name already exists"
        );
    }

    /**
     * Set connection
     *
     * @param  string $connection
     * @return Connection
     */
    /**
     * Set the connection driver to use
     *
     * @param  string $connection
     * @return $this
     */
    public function setConnection(string $connection): self
    {
        $this->connection = $connection;
        return $this;
    }

    /**
     * __call
     *
     * @param  string $name
     * @param  array  $arguments
     * @return mixed|null
     * @throws MethodCallException
     */
    /**
     * Proxy method calls to the underlying adapter
     *
     * @param  string $name
     * @param  array  $arguments
     * @return mixed
     * @throws MethodCallException
     */
    public function __call(string $name, array $arguments)
    {
        $adapter = $this->getAdapter();
        if (method_exists($adapter, $name)) {
            return $adapter->$name(...$arguments);
        }
        $class = get_class($adapter);
        throw new MethodCallException("Call to undefined method {$class}->{$name}()");
    }

    /**
     * Get the define adapter
     *
     * @return QueueAdapter
     */
    /**
     * Get the configured adapter instance
     *
     * @return QueueAdapter
     * @throws ConnexionException
     */
    public function getAdapter(): QueueAdapter
    {
        $driver = $this->connection ?: $this->config['default'];
        if (!isset(static::$connections[$driver])) {
            throw new ConnexionException("Queue driver '{$driver}' is not supported.");
        }
        if (!isset($this->config['connections'][$driver])) {
            throw new ConnexionException("No configuration found for queue driver '{$driver}'.");
        }
        $adapterClass = static::$connections[$driver];
        /** @var QueueAdapter $adapter */
        $adapter = new $adapterClass();
        return $adapter->configure($this->config['connections'][$driver]);
    }
}
