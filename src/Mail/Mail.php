<?php

declare(strict_types=1);

namespace Bow\Mail;

use Bow\Mail\Contracts\MailAdapterInterface;
use Bow\Mail\Adapters\NativeAdapter;
use Bow\Mail\Adapters\SesAdapter;
use Bow\Mail\Adapters\SmtpAdapter;
use Bow\Mail\Exception\MailException;
use Bow\View\View;
use ErrorException;

/**
 * @method mixed view(string $template, array $data, callable $cb)
 * @method mixed queue(string $template, array $data, callable $cb)
 * @method mixed queueOn(string $queue, string $template, array $data, callable $cb)
 * @method mixed send($view, array|callable $data, ?callable $cb = null)
 * @method mixed raw(string|array $to, string $subject, string $data, array $headers = [])
 */
class Mail
{
    /**
     * The driver collector
     *
     * @var array
     */
    private static array $drivers = [
        'smtp' => SmtpAdapter::class,
        'mail' => NativeAdapter::class,
        'ses' => SesAdapter::class,
    ];

    /**
     * The mail driver instance
     *
     * @var ?MailAdapterInterface
     */
    private static ?MailAdapterInterface $instance = null;

    /**
     * The mail configuration
     *
     * @var array
     */
    private static array $config;

    /**
     * Mail constructor
     *
     * @param array $config
     * @throws MailException
     */
    public function __construct(array $config = [])
    {
        static::configure($config);
    }

    /**
     * Configure la classe Mail
     *
     * @param array $config
     * @return MailAdapterInterface
     * @throws MailException
     */
    public static function configure(array $config = []): MailAdapterInterface
    {
        if (empty(static::$config)) {
            static::$config = $config;
        }

        if (!isset($config['driver'])) {
            throw new MailException(
                "The driver is not defined.",
                E_USER_ERROR
            );
        }

        if (!in_array($config['driver'], array_keys(static::$drivers))) {
            throw new MailException(
                "The driver is not defined.",
                E_USER_ERROR
            );
        }

        $name = $config['driver'];
        $driver = static::$drivers[$name];

        if (!static::$instance instanceof $driver) {
            static::$instance = new $driver($config[$name]);
        }

        return static::$instance;
    }

    /**
     * Get mail instance
     *
     * @return MailAdapterInterface
     */
    public static function getInstance(): MailAdapterInterface
    {
        return static::$instance;
    }

    /**
     * Send mail similar to the PHP mail function
     *
     * @param string|array $to
     * @param string $subject
     * @param string $data
     * @param array $headers
     * @return mixed
     */
    public static function raw(string|array $to, string $subject, string $data, array $headers = []): mixed
    {
        $to = (array)$to;

        $envelop = new Envelop();

        $envelop->toList($to)->subject($subject)->setMessage($data);

        foreach ($headers as $key => $value) {
            $envelop->addHeader($key, $value);
        }

        return static::$instance->send($envelop);
    }

    /**
     * The method thad send the configured mail
     *
     * @param string $view
     * @param callable|array $data
     * @param callable|null $cb
     * @return bool
     */
    public static function send(string $view, callable|array $data, ?callable $cb = null): bool
    {
        if (is_null($cb)) {
            $cb = $data;
            $data = [];
        }

        $content = View::parse($view, $data)->getContent();

        $envelop = new Envelop();
        $envelop->setMessage($content);

        call_user_func_array($cb, [$envelop]);

        return static::$instance->send($envelop);
    }

    /**
     * Send env on queue
     *
     * @param string $template
     * @param array $data
     * @param callable $cb
     * @return void
     */
    public static function queue(string $template, array $data, callable $cb): void
    {
        $envelop = new Envelop();

        call_user_func_array($cb, [$envelop]);

        $producer = new MailQueueProducer($template, $data, $envelop);

        queue($producer);
    }

    /**
     * Send env on specific queue
     *
     * @param string $queue
     * @param string $template
     * @param array $data
     * @param callable $cb
     * @return void
     */
    public static function queueOn(string $queue, string $template, array $data, callable $cb): void
    {
        $envelop = new Envelop();

        call_user_func_array($cb, [$envelop]);

        $producer = new MailQueueProducer($template, $data, $envelop);

        $producer->setQueue($queue);

        queue($producer);
    }

    /**
     * Send mail later
     *
     * @param integer $delay
     * @param string $template
     * @param array $data
     * @param callable $cb
     * @return void
     */
    public static function later(int $delay, string $template, array $data, callable $cb): void
    {
        $envelop = new Envelop();

        call_user_func_array($cb, [$envelop]);

        $producer = new MailQueueProducer($template, $data, $envelop);

        $producer->setDelay($delay);

        queue($producer);
    }

    /**
     * Send mail later on specific queue
     *
     * @param integer $delay
     * @param string $queue
     * @param string $template
     * @param array $data
     * @param callable $cb
     * @return void
     */
    public static function laterOn(int $delay, string $queue, string $template, array $data, callable $cb): void
    {
        $envelop = new Envelop();

        call_user_func_array($cb, [$envelop]);

        $producer = new MailQueueProducer($template, $data, $envelop);

        $producer->setQueue($queue);
        $producer->setDelay($delay);

        queue($producer);
    }

    /**
     * Modify the smtp|mail|ses driver
     *
     * @param string $driver
     * @return MailAdapterInterface
     * @throws MailException
     */
    public static function setDriver(string $driver): MailAdapterInterface
    {
        if (static::$config == null) {
            throw new MailException(
                'Please configure the Mail service.'
            );
        }

        if (in_array($driver, array_keys(static::$drivers))) {
            throw new MailException(
                "The driver $driver is not available"
            );
        }

        static::$config['driver'] = $driver;

        return static::configure(static::$config);
    }

    /**
     * Push new driver
     *
     * @param string $name
     * @param string $class_name
     * @return bool
     */
    public function pushDriver(string $name, string $class_name): bool
    {
        if (array_key_exists($name, static::$drivers)) {
            return false;
        }

        static::$drivers[$name] = $class_name;

        return true;
    }

    /**
     * __call
     *
     * @param string $name
     * @param array $arguments
     * @return mixed
     * @throws ErrorException
     */
    public function __call(string $name, array $arguments = [])
    {
        if (method_exists(static::class, $name)) {
            return call_user_func_array([static::class, $name], $arguments);
        }

        throw new ErrorException(
            "This function $name does not existe",
            E_ERROR
        );
    }
}
