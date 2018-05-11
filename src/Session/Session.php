<?php

namespace Bow\Session;

use function array_key_exists;
use Bow\Security\Crypto;
use InvalidArgumentException;
use Bow\Interfaces\CollectionAccessStatic;
use function session_destroy;

/**
 * Class Session
 *
 * @author  Franck Dakia <dakiafranck@gmail.com>
 * @package Bow\Support
 */
class Session implements CollectionAccessStatic
{
    /**
     * @var array
     */
    const SESSION_CORE_KEY = [
        "flash" => "__bow.flash",
        "old" => "__bow.old",
        "listener" => "__bow.event.listener",
        "csrf" => "__bow.csrf",
        "cookie" => "__bow.cookie.secure",
        "cache" => "__bow.session.key.cache"
    ];

    /**
     * Session constructor.
     */
    public function __construct()
    {
        static::start();
    }

    /**
     * Session starteur.
     */
    public static function start()
    {
        if (PHP_SESSION_ACTIVE == session_status()) {
            return true;
        }

        session_name("BSESSID");

        if (!isset($_COOKIE["BSESSID"])) {
            session_id(hash("sha256", Crypto::encrypt(uniqid(microtime(false)))));
        }

        $started = @session_start();

        if (!isset($_SESSION[static::SESSION_CORE_KEY['csrf']])) {
            $_SESSION[static::SESSION_CORE_KEY['csrf']] = new \stdClass();
        }
        if (!isset($_SESSION[static::SESSION_CORE_KEY['cache']])) {
            $_SESSION[static::SESSION_CORE_KEY['cache']] = [];
        }
        if (!isset($_SESSION[static::SESSION_CORE_KEY['listener']])) {
            $_SESSION[static::SESSION_CORE_KEY['listener']] = [];
        }
        if (!isset($_SESSION[static::SESSION_CORE_KEY['flash']])) {
            $_SESSION[static::SESSION_CORE_KEY['flash']] = [];
        }
        if (!isset($_SESSION[static::SESSION_CORE_KEY['old']])) {
            $_SESSION[static::SESSION_CORE_KEY['old']] = [];
        }

        return $started;
    }

    /**
     * Permet de filter les variables définie par l'utilisateur
     * et celles utilisé par le framework.
     *
     * @return array
     */
    private static function filter()
    {
        $arr = [];
        static::start();

        foreach ($_SESSION as $key => $value) {
            if (!array_key_exists($key, static::SESSION_CORE_KEY)) {
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
     *
     * @return boolean
     */
    public static function has($key, $strict = false)
    {
        static::start();

        if (!isset($_SESSION[static::SESSION_CORE_KEY['cache']][$key])) {
            return isset($_SESSION[static::SESSION_CORE_KEY['flash']][$key]);
        }

        return true;
    }

    /**
     * Permet de vérifier si une colléction est vide.
     *
     * @return boolean
     */
    public static function isEmpty()
    {
        return empty(self::filter());
    }

    /**
     * Permet de récupérer une valeur ou la colléction de valeur.
     *
     * @param string $key=null
     * @param mixed  $default
     *
     * @return mixed
     */
    public static function get($key, $default = null)
    {
        static::start();

        if (isset($_SESSION[static::SESSION_CORE_KEY['flash']][$key])) {
            $flash = $_SESSION[static::SESSION_CORE_KEY['flash']][$key];
            unset($_SESSION[static::SESSION_CORE_KEY['flash']][$key]);
            return $flash;
        }

        if (static::has($key)) {
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
     * @param string|int $key   La clé de la donnée à
     *                          ajouter
     * @param mixed      $value La donnée à
     *                          ajouter
     * @param boolean    $next  Elle permet, si elle est a true d'ajouter la donnée si la
     *                          clé existe Dans un tableau
     *
     * @throws InvalidArgumentException
     * @return mixed
     */
    public static function add($key, $value, $next = false)
    {
        static::start();

        if (!isset($_SESSION[static::SESSION_CORE_KEY['cache']])) {
            $_SESSION[static::SESSION_CORE_KEY['cache']] = [];
        }

        $_SESSION[static::SESSION_CORE_KEY['cache']][$key] = true;

        if ($next == false) {
            return $_SESSION[$key] = $value;
        }

        if (!static::has($key)) {
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
    public static function all()
    {
        return static::filter();
    }

    /**
     * remove, supprime une entrée dans la colléction
     *
     * @param string $key La clé de l'élément a supprimé
     *
     * @return mixed
     */
    public static function remove($key)
    {
        self::start();

        $old = null;

        if (static::has($key)) {
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
    public static function set($key, $value)
    {
        static::start();

        $old = null;
        $_SESSION[static::SESSION_CORE_KEY['cache']][$key] = true;

        if (static::has($key)) {
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
    public static function flash($key, $message = null)
    {
        static::start();

        if (!static::has(static::SESSION_CORE_KEY['flash'])) {
            $_SESSION[static::SESSION_CORE_KEY['flash']] = [];
        }

        if ($message !== null) {
            $_SESSION[static::SESSION_CORE_KEY['flash']][$key] = $message;
            return true;
        }

        return isset($_SESSION[static::SESSION_CORE_KEY['flash']][$key]) ? $_SESSION[static::SESSION_CORE_KEY['flash']][$key] : null;
    }

    /**
     * Retourne la liste des données de la session sous forme de tableau.
     *
     * @return array
     */
    public static function toArray()
    {
        return self::filter();
    }

    /**
     * Vide le système de flash.
     */
    public static function clearFash()
    {
        static::start();
        $_SESSION[static::SESSION_CORE_KEY['flash']] = [];
    }

    /**
     * clear, permet de vider le cache sauf csrf|bow.flash
     */
    public static function clear()
    {
        static::start();

        foreach (static::filter() as $key => $value) {
            unset($_SESSION[static::SESSION_CORE_KEY['cache']][$key]);
            unset($_SESSION[$key]);
        }
    }

    /**
     * Permet de vide la session
     */
    public static function flush()
    {
        session_destroy();
        static::start();
    }


    /**
     * __toString
     *
     * @return string
     */
    public function __toString()
    {
        static::start();
        return json_encode(static::filter());
    }

    /**
     * __call
     *
     * @param  string $name
     * @param  array  $arguments
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        if (method_exists(static::class, $name)) {
            return call_user_func_array([static::class, $name], $arguments);
        }

        throw new \BadMethodCallException('La methode ' . $name . ' n\'exist pas.');
    }
}
