<?php

namespace Bow\Support;

class Cookie
{
    /**
     * @var array
     */
    private static $isDecrypt = [];
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
            if (! static::$isDecrypt[$key]) {
                static::$isDecrypt[$key] = true;
                return Security::decrypt($_COOKIE[$key]);
            }
            return $_COOKIE[$key];
        }

        foreach($_COOKIE as $value) {
            if (! static::$isDecrypt[$key]) {
                static::$isDecrypt[$key] = true;
                $_COOKIE[$key] = Security::decrypt($value);
            }
        }
        return $_COOKIE;
    }

    /**
     * add, permet d'ajouter une value dans le tableau de cookie.
     * 
     * @param string|int $key, la clé du cookie
     * @param mixed $data la donnée a associée
     * @param int $time le temps de vie du cookie
     * @param string $path le path de reconnaissance
     * @param string $domain le domaine sur lequel sera envoyé le cookie
     * @param bool $secure définie la sécurité
     * @param bool $http définie si c'est seulement le protocole http
     * @return bool
     */
    public static function add($key, $data, $time = 3600, $path = null, $domain = null, $secure = false, $http = true)
    {
        static::$isDecrypt[$key] = false;
        return setcookie($key, Security::encrypt($data), $time, $path, $domain, $secure, $http);
    }

    /**
     * remove, supprime une entrée dans la table
     * 
     * @param string $key
     * @return self
     */
    public static function remove($key)
    {
        $old = null;

        if (static::has($key)) {
            if (! static::$isDecrypt[$key]) {
                $old = $_COOKIE[$key];
                $old = Security::decrypt($old);
                unset(static::$isDecrypt[$key]);
            }
            unset($_COOKIE[$key]);
        }

        return $old;
    }
}