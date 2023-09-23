<?php

declare(strict_types=1);

namespace Bow\Mail;

use Bow\Mail\Contracts\MailDriverInterface;
use Bow\Mail\Exception\MailException;
use Bow\View\View;

/**
 * @method mixed view(string $template, array $data, callable $cb)
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
        'smtp' => \Bow\Mail\Driver\SmtpDriver::class,
        'mail' => \Bow\Mail\Driver\NativeDriver::class,
        'ses' => \Bow\Mail\Driver\SesDriver::class,
    ];

    /**
     * The mail driver instance
     *
     * @var MailDriverInterface
     */
    private static ?MailDriverInterface $instance = null;

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
     * @param  array $config
     * @throws MailException
     * @return MailDriverInterface
     */
    public static function configure(array $config = []): MailDriverInterface
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
     * Get mail instance
     *
     * @return MailDriverInterface
     */
    public static function getInstance(): MailDriverInterface
    {
        return static::$instance;
    }

    /**
     * @inheritdoc
     */
    public static function send(string $view, callable|array $data, ?callable $cb = null)
    {
        if (is_null($cb)) {
            $cb = $data;
            $data = [];
        }

        $content = View::parse($view, $data)->getContent();

        $message = new Message();
        $message->setMessage($content);

        call_user_func_array($cb, [$message]);

        return static::$instance->send($message);
    }

    /**
     * Send mail similar to the PHP mail function
     *
     * @param  string|array $to
     * @param  string       $subject
     * @param  string       $data
     * @param  array        $headers
     * @return mixed
     */
    public static function raw(string|array $to, string $subject, string $data, array $headers = [])
    {
        $to = (array) $to;

        $message = new Message();

        $message->toList($to)->subject($subject)->setMessage($data);

        foreach ($headers as $key => $value) {
            $message->addHeader($key, $value);
        }

        return static::$instance->send($message);
    }

    /**
     * Send mail similar to the PHP mail function
     *
     * @param  string $template
     * @param  array  $data
     * @param  callable $cb
     * @return mixed
     */
    public static function view(string $template, array $data, callable $cb)
    {
        $message = new Message();

        $data = View::parse($template, $data)->getContent();

        $message->setMessage($data);

        call_user_func_array($cb, [$message]);

        return static::$instance->send($message);
    }

    /**
     * Modify the smtp|mail|ses driver
     *
     * @param string $driver
     * @return MailDriverInterface
     * @throws MailException
     */
    public static function setDriver(string $driver): MailDriverInterface
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
     * __call
     *
     * @param  string $name
     * @param  array  $arguments
     * @return mixed
     * @throws \ErrorException
     */
    public function __call($name, $arguments)
    {
        if (method_exists(static::class, $name)) {
            return call_user_func_array([static::class, $name], $arguments);
        }

        throw new \ErrorException(
            "This function $name does not existe",
            E_ERROR
        );
    }
}
