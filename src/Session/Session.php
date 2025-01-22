<?php

declare(strict_types=1);

namespace Bow\Session;

use BadMethodCallException;
use Bow\Contracts\CollectionInterface;
use Bow\Security\Crypto;
use Bow\Session\Driver\ArrayDriver;
use Bow\Session\Driver\DatabaseDriver;
use Bow\Session\Driver\FilesystemDriver;
use Bow\Session\Exception\SessionException;
use InvalidArgumentException;
use stdClass;

class Session implements CollectionInterface
{
    /**
     * The internal session variable
     *
     * @var array
     */
    public const CORE_SESSION_KEY = [
        "flash" => "__bow.flash",
        "old" => "__bow.old",
        "listener" => "__bow.event.listener",
        "csrf" => "__bow.csrf",
        "cookie" => "__bow.cookie.secure",
        "cache" => "__bow.session.key.cache"
    ];
    /**
     * The instance of Session
     *
     * @var ?Session
     */
    private static ?Session $instance = null;
    /**
     * The session available driver
     *
     * @var array
     */
    private array $driver = [
        'database' => DatabaseDriver::class,
        'array' => ArrayDriver::class,
        'file' => FilesystemDriver::class,
    ];
    /**
     * The session configuration
     *
     * @var array
     */
    private array $config;

    /**
     * Session constructor.
     *
     * @param array $config
     * @throws SessionException
     */
    private function __construct(array $config)
    {
        if (!isset($config['driver'])) {
            throw new SessionException("The session driver is undefined");
        }

        if (!isset($this->driver[$config['driver']])) {
            throw new SessionException("The session driver is not support");
        }

        // We merge configuration
        $this->config = array_merge([
            'name' => 'Bow',
            'path' => '/',
            'domain' => null,
            'secure' => false,
            'httponly' => false,
            'save_path' => null,
        ], $config);
    }

    /**
     * Configure session instance
     *
     * @param array $config
     * @return Session
     * @throws SessionException
     */
    public static function configure(array $config): Session
    {
        if (static::$instance == null) {
            static::$instance = new Session($config);
        }

        return static::$instance;
    }

    /**
     * Get session singleton
     *
     * @return ?Session
     */
    public static function getInstance(): ?Session
    {
        return static::$instance;
    }

    /**
     * Generate session
     *
     * @throws SessionException
     */
    public function regenerate(): void
    {
        $this->flush();
        $this->start();
    }

    /**
     * Allows you to empty the session
     */
    public function flush(): void
    {
        session_destroy();
    }

    /**
     * Session starter.
     *
     * @return bool
     * @throws SessionException
     */
    public function start(): bool
    {
        if (PHP_SESSION_ACTIVE == session_status()) {
            return true;
        }

        // Load session driver
        $this->initializeDriver();

        // Set the cookie param
        $this->setCookieParameters();

        // Boot session
        $started = $this->boot();

        // Init internet session manager
        $this->initializeInternalSessionStorage();

        return $started;
    }

    /**
     * Load session driver
     *
     * @return void
     * @throws SessionException
     */
    private function initializeDriver(): void
    {
        // We Apply session cookie name
        @session_name($this->config['name']);

        if (!isset($_COOKIE[$this->config['name']])) {
            @session_id(hash("sha256", $this->generateId()));
        }

        // We create get driver
        $driver = $this->driver[$this->config['driver']] ?? null;

        if (is_null($driver)) {
            throw new SessionException(
                'The driver ' . $this->config['driver'] . ' is not valid'
            );
        }

        switch ($this->config['driver']) {
            case 'file':
                @session_save_path(realpath($this->config['save_path']));
                $handler = new $driver(realpath($this->config['save_path']));
                break;
            case 'database':
                $handler = new $driver($this->config['database'], request()->ip());
                break;
            case 'array':
                $handler = new $driver();
                break;
            default:
                throw new SessionException(
                    'Cannot set the session driver'
                );
        }

        // Set the session driver
        if (!@session_set_save_handler($handler, true)) {
            throw new SessionException(
                'Cannot set the session driver'
            );
        }
    }

    /**
     * Generate session ID
     *
     * @return string
     */
    private function generateId(): string
    {
        return Crypto::encrypt(uniqid(microtime()));
    }

    /**
     * Set session cookie params
     *
     * @return void
     */
    private function setCookieParameters(): void
    {
        session_set_cookie_params(
            (int)$this->config["lifetime"],
            $this->config["path"],
            $this->config['domain'],
            $this->config["secure"],
            $this->config["httponly"]
        );
    }

    /**
     * Start session natively
     *
     * @return bool
     * @throws SessionException
     */
    private function boot(): bool
    {
        if (!headers_sent()) {
            return @session_start();
        }

        throw new SessionException('Headers already sent. Cannot start session.');
    }

    /**
     * Load internal session
     *
     * @return void
     */
    private function initializeInternalSessionStorage(): void
    {
        if (!isset($_SESSION[static::CORE_SESSION_KEY['csrf']])) {
            $_SESSION[static::CORE_SESSION_KEY['csrf']] = new stdClass();
        }

        if (!isset($_SESSION[static::CORE_SESSION_KEY['cache']])) {
            $_SESSION[static::CORE_SESSION_KEY['cache']] = [];
        }

        if (!isset($_SESSION[static::CORE_SESSION_KEY['listener']])) {
            $_SESSION[static::CORE_SESSION_KEY['listener']] = [];
        }

        if (!isset($_SESSION[static::CORE_SESSION_KEY['flash']])) {
            $_SESSION[static::CORE_SESSION_KEY['flash']] = [];
        }

        if (!isset($_SESSION[static::CORE_SESSION_KEY['old']])) {
            $_SESSION[static::CORE_SESSION_KEY['old']] = [];
        }
    }

    /**
     * Allows checking for the existence of a key in the session collection
     *
     * @param string $key
     * @return bool
     * @throws SessionException
     */
    public function exists(string $key): bool
    {
        return $this->has($key, true);
    }

    /**
     * Allows checking for the existence of a key in the session collection
     *
     * @param string|int $key
     * @param bool $strict
     * @return bool
     * @throws SessionException
     */
    public function has(string|int $key, bool $strict = false): bool
    {
        $this->start();

        $cache = $_SESSION[static::CORE_SESSION_KEY['cache']];
        $flash = $_SESSION[static::CORE_SESSION_KEY['flash']];

        if (!$strict) {
            if (isset($cache[$key])) {
                return true;
            }

            if (isset($flash[$key])) {
                return true;
            }

            return isset($_SESSION[$key]);
        }

        $value = $cache[$key] ?? null;

        if (!is_null($value)) {
            return count((array)$value) > 0;
        }

        $value = $flash[$key] ?? null;

        if (!is_null($value)) {
            return count((array)$value) > 0;
        }

        if (isset($_SESSION[$key])) {
            return count((array)$_SESSION[$key]) > 0;
        }

        return false;
    }

    /**
     * Check whether a collection is empty.
     *
     * @return bool
     * @throws SessionException
     */
    public function isEmpty(): bool
    {
        return empty($this->filter());
    }

    /**
     * Allows to filter user defined variables
     * and those used by the framework.
     *
     * @return array
     * @throws SessionException
     */
    private function filter(): array
    {
        $arr = [];

        $this->start();

        foreach ($_SESSION as $key => $value) {
            if (!array_key_exists($key, static::CORE_SESSION_KEY)) {
                $arr[$key] = $value;
            }
        }

        return $arr;
    }

    /**
     * Retrieves a value or value collection.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     * @throws SessionException
     */
    public function get(mixed $key, mixed $default = null): mixed
    {
        $content = $this->flash($key);

        if (!is_null($content)) {
            return $content;
        }

        if ($this->has($key)) {
            return $_SESSION[$key] ?? null;
        }

        if (is_callable($default)) {
            return $default();
        }

        return $default;
    }

    /**
     * Add flash data
     * After the data recovery is automatic deleted
     *
     * @param string|int $key
     * @param mixed $message
     * @return mixed
     * @throws SessionException
     */
    public function flash(string|int $key, ?string $message = null): mixed
    {
        $this->start();

        if ($message != null) {
            $_SESSION[static::CORE_SESSION_KEY['flash']][$key] = $message;

            return true;
        }

        $flash = $_SESSION[static::CORE_SESSION_KEY['flash']];

        $content = $flash[$key] ?? null;

        $tmp = array_filter($flash, function ($i) use ($key) {
            return $i != $key;
        }, ARRAY_FILTER_USE_KEY);

        $_SESSION[static::CORE_SESSION_KEY['flash']] = $tmp;

        return $content;
    }

    /**
     * The add alias
     *
     * @throws SessionException
     * @see Session::add
     */
    public function put(string|int $key, mixed $value, $next = false): mixed
    {
        return $this->add($key, $value, $next);
    }

    /**
     * Add an entry to the collection
     *
     * @param string|int $key
     * @param mixed $data
     * @param boolean $next
     * @return mixed
     * @throws InvalidArgumentException|SessionException
     */
    public function add(string|int $key, mixed $data, bool $next = false): mixed
    {
        $this->start();

        $_SESSION[static::CORE_SESSION_KEY['cache']][$key] = true;

        if ($next === false) {
            return $_SESSION[$key] = $data;
        }

        if (!$this->has($key)) {
            $_SESSION[$key] = [];
        }

        if (!is_array($_SESSION[$key])) {
            $_SESSION[$key] = [$_SESSION[$key]];
        }

        $_SESSION[$key] = array_merge($_SESSION[$key], [$data]);

        return $data;
    }

    /**
     * Returns the list of session variables
     *
     * @return array
     * @throws SessionException
     */
    public function all(): array
    {
        return $this->filter();
    }

    /**
     * Delete an entry in the collection
     *
     * @param string|int $key
     *
     * @return mixed
     * @throws SessionException
     */
    public function remove(string|int $key): mixed
    {
        $this->start();

        $old = null;

        if ($this->has($key)) {
            $old = $_SESSION[$key];
        }

        unset($_SESSION[$key]);
        unset($_SESSION[static::CORE_SESSION_KEY['cache']][$key]);
        unset($_SESSION[static::CORE_SESSION_KEY['flash']][$key]);

        return $old;
    }

    /**
     * set
     *
     * @param string|int $key
     * @param mixed $value
     *
     * @return mixed
     * @throws SessionException
     */
    public function set(string|int $key, mixed $value): mixed
    {
        $this->start();

        $_SESSION[static::CORE_SESSION_KEY['cache']][$key] = true;

        if (!$this->has($key)) {
            $_SESSION[$key] = $value;

            return null;
        }

        $old = $_SESSION[$key];

        $_SESSION[$key] = $value;

        return $old;
    }

    /**
     * Returns the list of session data as an array.
     *
     * @return array
     * @throws SessionException
     */
    public function toArray(): array
    {
        return $this->filter();
    }

    /**
     * Empty the flash system.
     * @throws SessionException
     */
    public function clearFlash(): void
    {
        $this->start();

        $_SESSION[static::CORE_SESSION_KEY['flash']] = [];
    }

    /**
     * Allows to clear the cache except csrf and __bow.flash
     *
     * @throws SessionException
     */
    public function clear(): void
    {
        $this->start();

        foreach ($this->filter() as $key => $value) {
            unset($_SESSION[static::CORE_SESSION_KEY['cache']][$key]);

            unset($_SESSION[$key]);
        }
    }

    /**
     * Returns the list of session data as a toObject.
     *
     * @return array
     */
    public function toObject(): array
    {
        throw new BadMethodCallException("Bad method called");
    }

    /**
     * __toString
     *
     * @return string
     * @throws SessionException
     */
    public function __toString(): string
    {
        $this->start();

        return json_encode($this->filter());
    }
}
