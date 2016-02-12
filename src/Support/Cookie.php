<?php

namespace Bow\Support;

class Cookie
{
    /**
     * @static self
     */
	private static $instance = null;

    /**
     * @access private
     */
    private function __construct() {}

    /**
     * @access private
     */
    private function __clone() {}

    /**
     * has, vérifie l'existance une clé dans la colléction de session
     * 
     * @param string $key
     * 
     * @return boolean
     */
    public static function has($key)
    {
        return isset($_COOKIE[$key]);
    }

    /**
     * isEmpty, vérifie si une colléction est vide.
     * 
     * @return boolean
     */
    public static function IsEmpty()
    {
        return empty($_COOKIE);
    }

    /**
     * get, permet de récupérer une valeur ou la colléction de valeur de cookie.
     *
     * @param string $key=null
     * 
     * @return mixed
     */
    public static function get($key = null)
    {
        if (static::has($key)) {
            return $_COOKIE[$key];
        }

        return $_COOKIE;
    }

    /**
     * add, permet d'ajouter une value dans le tableau de cookie.
     * 
     * @param string|int $key
     * @param mixed $data
     * @param int $time=3600
     * @param string $path=null
     * @param string $domain=null
     * @param bool $secure=false
     * @param bool $http=false
     * 
     */
    public static function add($key, $data, $time = 3600, $path = null, $domain = null, $secure = false, $http = true)
    {
        return setcookie($key, $data, $time, $path, $domain, $secure, $http);
    }

    /**
     * remove, supprime une entrée dans la table
     * 
     * @param string $key
     * 
     * @return self
     */
    public static function remove($key)
    {
        $old = false;

        if (static::has($key)) {
            $old = $_COOKIE[$key];
        }

        unset($_COOKIE[$key]);

        return $old;
    }
}