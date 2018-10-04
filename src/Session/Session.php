<?php

namespace Bow\Session;

use Bow\Contrats\CollectionInterface;
use Bow\Security\Crypto;
use InvalidArgumentException;

class Session implements CollectionInterface
{
    /**
     * @var array
     */
    const CORE_KEY = [
        "flash" => "__bow.flash",
        "old" => "__bow.old",
        "listener" => "__bow.event.listener",
        "csrf" => "__bow.csrf",
        "cookie" => "__bow.cookie.secure",
        "cache" => "__bow.session.key.cache"
    ];

    /**
     * @var Session
     */
    private static $instance;

    /**
     * @var array
     */
    private $config = [
        'name' => 'BSESSID'
    ];

    /**
     * Session constructor.
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->config = array_merge($this->config, $config);

        $this->start();
    }

    /**
     * Configure
     *
     * @param array $config
     * @return mixed
     */
    public static function configure($config)
    {
        if (static::$instance == null) {
            static::$instance = new static($config);
        }

        return static::$instance;
    }

    /**
     * Get session singleton
     *
     * @return mixed
     */
    public static function getInstance()
    {
        return static::$instance;
    }

    /**
     * Session starteur.
     */
    public function start()
    {
        if (PHP_SESSION_ACTIVE == session_status()) {
            return true;
        }

        session_name($this->config['name']);

        if (!isset($_COOKIE["BSESSID"])) {
            session_id(hash("sha256", $this->generateId()));
        }

        $started = @session_start();

        if (!isset($_SESSION[static::CORE_KEY['csrf']])) {
            $_SESSION[static::CORE_KEY['csrf']] = new \stdClass();
        }

        if (!isset($_SESSION[static::CORE_KEY['cache']])) {
            $_SESSION[static::CORE_KEY['cache']] = [];
        }

        if (!isset($_SESSION[static::CORE_KEY['listener']])) {
            $_SESSION[static::CORE_KEY['listener']] = [];
        }

        if (!isset($_SESSION[static::CORE_KEY['flash']])) {
            $_SESSION[static::CORE_KEY['flash']] = [];
        }

        if (!isset($_SESSION[static::CORE_KEY['old']])) {
            $_SESSION[static::CORE_KEY['old']] = [];
        }

        return $started;
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
     * Permet de filter les variables définie par l'utilisateur
     * et celles utilisé par le framework.
     *
     * @return array
     */
    private function filter()
    {
        $arr = [];

        $this->start();

        foreach ($_SESSION as $key => $value) {
            if (!array_key_exists($key, static::CORE_KEY)) {
                $arr[$key] = $value;
            }
        }

        return $arr;
    }

    /**
     * Permet de vérifier l'existance une clé dans la colléction de session
     *
     * @param string $key
     * @param bool   $strict
     * @return boolean
     */
    public function has($key, $strict = false)
    {
        $this->start();

        if (!$strict) {
            if (!isset($_SESSION[static::CORE_KEY['cache']][$key])) {
                return isset($_SESSION[static::CORE_KEY['flash']][$key]);
            }

            return true;
        }

        if (!isset($_SESSION[static::CORE_KEY['cache']][$key])) {
            if (isset($_SESSION[static::CORE_KEY['flash']][$key])) {
                $value = $_SESSION[static::CORE_KEY['flash']][$key];

                return !is_null($value);
            }

            return false;
        }

        $value = $_SESSION[static::CORE_KEY['cache']][$key];

        return !is_null($value);
    }

    /**
     * Permet de vérifier l'existance une clé dans la colléction de session
     *
     * @param string $key
     * @return boolean
     */
    public function exists($key)
    {
        return  $this->has($key, true);
    }

    /**
     * Permet de vérifier si une colléction est vide.
     *
     * @return boolean
     */
    public function isEmpty()
    {
        return empty($this->filter());
    }

    /**
     * Permet de récupérer une valeur ou la colléction de valeur.
     *
     * @param string $key=null
     * @param mixed  $default
     *
     * @return mixed
     */
    public function get($key, $default = null)
    {
        $this->start();

        if (isset($_SESSION[static::CORE_KEY['flash']][$key])) {
            $flash = $_SESSION[static::CORE_KEY['flash']][$key];

            unset($_SESSION[static::CORE_KEY['flash']][$key]);

            return $flash;
        }

        if ($this->has($key)) {
            return $_SESSION[$key];
        }

        if (is_callable($default)) {
            return $default();
        }

        return $default;
    }

    /**
     * Permet d'ajouter une entrée dans la colléction
     *
     * @param string|int $key
     * @param mixed $value
     * @param boolean $next
     * @throws InvalidArgumentException
     * @return mixed
     */
    public function add($key, $value, $next = false)
    {
        $this->start();

        if (!isset($_SESSION[static::CORE_KEY['cache']])) {
            $_SESSION[static::CORE_KEY['cache']] = [];
        }

        $_SESSION[static::CORE_KEY['cache']][$key] = true;

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
     * Retourne la liste des variables de session
     *
     * @return array
     */
    public function all()
    {
        return  $this->filter();
    }

    /**
     * remove, supprime une entrée dans la colléction
     *
     * @param string $key La clé de l'élément a supprimé
     *
     * @return mixed
     */
    public function remove($key)
    {
        $this->start();

        $old = null;

        if ($this->has($key)) {
            $old = $_SESSION[$key];
        }

        unset($_SESSION[$key]);

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
    public function set($key, $value)
    {
        $this->start();

        $old = null;

        $_SESSION[static::CORE_KEY['cache']][$key] = true;

        if ($this->has($key)) {
            $old = $_SESSION[$key];

            $_SESSION[$key] = $value;

            return $old;
        }

        $_SESSION[$key] = $value;

        return $old;
    }

    /**
     * flash
     *
     * @param  mixed $key
     * @param  mixed $message
     * @return mixed
     */
    public function flash($key, $message = null)
    {
        $this->start();

        if (! $this->has(static::CORE_KEY['flash'])) {
            $_SESSION[static::CORE_KEY['flash']] = [];
        }

        if ($message !== null) {
            $_SESSION[static::CORE_KEY['flash']][$key] = $message;

            return true;
        }

        return isset($_SESSION[static::CORE_KEY['flash']][$key]) ?
            $_SESSION[static::CORE_KEY['flash']][$key] : null;
    }

    /**
     * Retourne la liste des données de la session sous forme de tableau.
     *
     * @return array
     */
    public function toArray()
    {
        return self::filter();
    }

    /**
     * Vide le système de flash.
     */
    public function clearFash()
    {
        $this->start();

        $_SESSION[static::CORE_KEY['flash']] = [];
    }

    /**
     * clear, permet de vider le cache sauf csrf|bow.flash
     */
    public function clear()
    {
        $this->start();

        foreach ($this->filter() as $key => $value) {
            unset($_SESSION[static::CORE_KEY['cache']][$key]);

            unset($_SESSION[$key]);
        }
    }

    /**
     * Permet de vide la session
     */
    public function flush()
    {
        session_destroy();
    }


    /**
     * __toString
     *
     * @return string
     */
    public function __toString()
    {
        $this->start();

        return json_encode($this->filter());
    }

    /**
     * __callStatic
     *
     * @param  string $name
     * @param  array  $arguments
     * @return mixed
     */
    public static function __callStatic($name, $arguments)
    {
        if (method_exists(static::$instance, $name)) {
            return call_user_func_array([static::$instance, $name], $arguments);
        }

        throw new \BadMethodCallException('La methode ' . $name . ' n\'exist pas.');
    }

    /**
     * @return array|void
     */
    public function toObject()
    {
        //
    }
}
