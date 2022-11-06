<?php

declare(strict_types=1);

namespace Bow\Session;

use Bow\Contracts\CollectionInterface;
use Bow\Security\Crypto;
use Bow\Session\Exception\SessionException;
use InvalidArgumentException;

class Session implements CollectionInterface
{
    /**
     * The internal session variable
     *
     * @var array
     */
    const CORE_SESSION_KEY = [
        "flash" => "__bow.flash",
        "old" => "__bow.old",
        "listener" => "__bow.event.listener",
        "csrf" => "__bow.csrf",
        "cookie" => "__bow.cookie.secure",
        "cache" => "__bow.session.key.cache"
    ];

    /**
     * The session available driver
     *
     * @var array
     */
    private array $driver = [
        'database' => \Bow\Session\Driver\DatabaseDriver::class,
        'array' => \Bow\Session\Driver\ArrayDriver::class,
        'file' => \Bow\Session\Driver\FilesystemDriver::class,
    ];

    /**
     * The instance of Session
     *
     * @var Session
     */
    private static ?Session $instance = null;

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
     * Session starter.
     *
     * @return bool
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

        // Init interne session manager
        $this->initializeInternalSessionStorage();

        return $started;
    }

    /**
     * Start session natively
     *
     * @return bool
     */
    private function boot(): bool
    {
        if (!headers_sent()) {
            return @session_start();
        }

        throw new SessionException('Headers already sent. Cannot start session.');
    }

    /**
     * Load session driver
     *
     * @return void
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
            throw new SessionException('The driver ' . $this->config['driver'] . ' is not valid');
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
                throw new SessionException('Cannot set the session driver');
                break;
        }

        // Set the session driver
        if (!@session_set_save_handler($handler, true)) {
            throw new SessionException('Cannot set the session driver');
        }
    }

    /**
     * Load internal session
     *
     * @return void
     */
    private function initializeInternalSessionStorage(): void
    {
        if (!isset($_SESSION[static::CORE_SESSION_KEY['csrf']])) {
            $_SESSION[static::CORE_SESSION_KEY['csrf']] = new \stdClass();
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
     * Set session cookie params
     *
     * @return void
     */
    private function setCookieParameters()
    {
        session_set_cookie_params(
            $this->config["lifetime"],
            $this->config["path"],
            $this->config['domain'],
            $this->config["secure"],
            $this->config["httponly"]
        );
    }

    /**
     * Generate session ID
     *
     * @return string
     */
    private function generateId()
    {
        return Crypto::encrypt(uniqid(microtime(false)));
    }

    /**
     * Generate session
     */
    public function regenerate()
    {
        $this->flush();
        $this->start();
    }

    /**
     * Allows to filter user defined variables
     * and those used by the framework.
     *
     * @return array
     */
    private function filter()
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
     * Allows checking for the existence of a key in the session collection
     *
     * @param string|int $key
     * @param bool $strict
     * @return bool
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
            return count((array) $value) > 0;
        }

        $value = $flash[$key] ?? null;
        
        if (!is_null($value)) {
            return count((array) $value) > 0;
        }

        if (isset($_SESSION[$key]) && !is_null($_SESSION[$key])) {
            return count((array) $_SESSION[$key]) > 0;
        }
        
        return false;
    }

    /**
     * Allows checking for the existence of a key in the session collection
     *
     * @param string $key
     * @return bool
     */
    public function exists($key): bool
    {
        return $this->has($key, true);
    }

    /**
     * Check whether a collection is empty.
     *
     * @return bool
     */
    public function isEmpty(): bool
    {
        return empty($this->filter());
    }

    /**
     * Retrieves a value or value collection.
     *
     * @param string $key
     * @param mixed  $default
     * @return mixed
     */
    public function get(mixed $key, mixed $default = null): mixed
    {
        $content = $this->flash($key);

        if (!is_null($content)) {
            return $content;
        }

        if (is_null($content) && $this->has($key)) {
            return $_SESSION[$key] ?? null;
        }

        if (is_callable($default)) {
            return $default();
        }

        return $default;
    }

    /**
     * Add an entry to the collection
     *
     * @param string|int $key
     * @param mixed $value
     * @param boolean $next
     * @throws InvalidArgumentException
     * @return mixed
     */
    public function add(string|int $key, mixed $value, $next = false): mixed
    {
        $this->start();

        $_SESSION[static::CORE_SESSION_KEY['cache']][$key] = true;

        if ($next == false) {
            return $_SESSION[$key] = $value;
        }

        if (! $this->has($key)) {
            $_SESSION[$key] = [];
        }

        if (!is_array($_SESSION[$key])) {
            $_SESSION[$key] = [$_SESSION[$key]];
        }

        $_SESSION[$key] = array_merge($_SESSION[$key], [$value]);

        return $value;
    }

    /**
     * The add alias
     *
     * @see \Bow\Session\Session::add
     */
    public function put(string|int $key, mixed $value, $next = false): mixed
    {
        return $this->add($key, $value, $next);
    }

    /**
     * Returns the list of session variables
     *
     * @return array
     */
    public function all(): array
    {
        return $this->filter();
    }

    /**
     * Delete an entry in the collection
     *
     * @param string $key
     *
     * @return mixed
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
     * @param string $key
     * @param mixed  $value
     *
     * @return mixed
     */
    public function set(string|int $key, mixed $value): mixed
    {
        $this->start();

        $old = null;

        $_SESSION[static::CORE_SESSION_KEY['cache']][$key] = true;

        if (!$this->has($key)) {
            $_SESSION[$key] = $value;

            return $old;
        }

        $old = $_SESSION[$key];

        $_SESSION[$key] = $value;

        return $old;
    }

    /**
     * Add flash data
     * After the data recovery is automatic deleted
     *
     * @param  string|int $key
     * @param  mixed $message
     * @return mixed
     */
    public function flash(string|int $key, ?string $message = null): mixed
    {
        $this->start();

        if ($message != null) {
            $_SESSION[static::CORE_SESSION_KEY['flash']][$key] = $message;

            return true;
        }

        $flash = $_SESSION[static::CORE_SESSION_KEY['flash']];

        $content = isset($flash[$key]) ? $flash[$key] : null;
        $tmp = [];

        foreach ($flash as $i => $value) {
            if ($i != $key) {
                $tmp[$i] = $value;
            }
        }

        $_SESSION[static::CORE_SESSION_KEY['flash']] = $tmp;

        return $content;
    }

    /**
     * Returns the list of session data as a array.
     *
     * @return array
     */
    public function toArray(): array
    {
        return $this->filter();
    }

    /**
     * Empty the flash system.
     */
    public function clearFash(): void
    {
        $this->start();

        $_SESSION[static::CORE_SESSION_KEY['flash']] = [];
    }

    /**
     * Allows to clear the cache except csrf and __bow.flash
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
     * Allows you to empty the session
     */
    public function flush(): void
    {
        session_destroy();
    }

    /**
     * Returns the list of session data as a toObject.
     *
     * @return array|void
     */
    public function toObject(): array
    {
        throw new \BadMethodCallException("Bad method called");
    }

    /**
     * __toString
     *
     * @return string
     */
    public function __toString(): string
    {
        $this->start();

        return json_encode($this->filter());
    }
}
